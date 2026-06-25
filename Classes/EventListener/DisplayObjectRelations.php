<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\EventListener;

use TYPO3\CMS\Backend\Controller\Event\BeforeFormEnginePageInitializedEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Zeroseven\Pagebased\Domain\Model\Contact;
use Zeroseven\Pagebased\Domain\Model\Topic;
use Zeroseven\Pagebased\Registration\RegistrationService;

class DisplayObjectRelations
{
    protected const TABLES = [
        Topic::class => 'tx_pagebased_domain_model_topic',
        Contact::class => 'tx_pagebased_domain_model_contact',
    ];

    public function __construct(private readonly FlashMessageService $flashMessageService) {}

    protected function getObjectsByPageIds(int $pid, string $pageIdMethode): array
    {
        $objects = [];

        foreach (RegistrationService::getRegistrations() as $registration) {
            if (
                method_exists($registration->getObject(), $pageIdMethode)
                && ($pageIds = $registration->getObject()->{$pageIdMethode}())
                && in_array($pid, $pageIds, true)
            ) {
                $objects[] = $registration->getObject()->getTitle();
            }
        }

        return $objects;
    }

    protected function showMessage(array $objects): void
    {
        $message = LocalizationUtility::translate(
            'LLL:EXT:pagebased/Resources/Private/Language/locallang_be.xlf:notification.objectRelations.description',
            'pagebased',
            [implode(', ', $objects)]
        );

        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, '', ContextualFeedbackSeverity::INFO);

        try {
            $messageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
            $messageQueue->enqueue($flashMessage);
        } catch (Exception) {
        }
    }

    public function __invoke(BeforeFormEnginePageInitializedEvent $beforeFormEnginePageInitializedEvent): void
    {
        $parsedBody = $beforeFormEnginePageInitializedEvent->getRequest()->getParsedBody();
        $queryParams = $beforeFormEnginePageInitializedEvent->getRequest()->getQueryParams();

        if (
            ($editConfiguration = $parsedBody['edit'] ?? $queryParams['edit'] ?? null)
            && ($table = array_key_first($editConfiguration))
            && (in_array($table, static::TABLES, true))
            && ($uid = (int)(array_key_first($editConfiguration[$table] ?? [])))
            && ($pid = (int)(BackendUtility::getRecord($table, $uid, 'pid')['pid'] ?? 0))
        ) {
            $objects = null;

            if ($table === self::TABLES[Topic::class] && $result = $this->getObjectsByPageIds($pid, 'getTopicPageIds')) {
                $objects = $result;
            }

            if ($table === self::TABLES[Contact::class] && $result = $this->getObjectsByPageIds($pid, 'getContactPageIds')) {
                $objects = $result;
            }

            if ($objects) {
                $this->showMessage($objects);
            }
        }
    }
}
