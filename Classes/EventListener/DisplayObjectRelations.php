<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\EventListener;

use TYPO3\CMS\Backend\Controller\Event\BeforeFormEnginePageInitializedEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Zeroseven\Rampage\Domain\Model\Contact;
use Zeroseven\Rampage\Domain\Model\Topic;
use Zeroseven\Rampage\Registration\RegistrationService;
use Zeroseven\Rampage\Utility\SettingsUtility;

class DisplayObjectRelations
{
    protected const TABLES = [
        Topic::class => 'tx_rampage_domain_model_topic',
        Contact::class => 'tx_rampage_domain_model_contact'
    ];

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
        $message = LocalizationUtility::translate('LLL:EXT:rampage/Resources/Private/Language/locallang_be.xlf:notification.objectRelations.description',
            SettingsUtility::EXTENSION_NAME, [implode(', ', $objects)]);

        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, '', AbstractMessage::INFO);

        try {
            $messageQueue = GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier();
            $messageQueue->enqueue($flashMessage);
        } catch (Exception $e) {
        }
    }

    public function __invoke(BeforeFormEnginePageInitializedEvent $event): void
    {
        $parsedBody = $event->getRequest()->getParsedBody();
        $queryParams = $event->getRequest()->getQueryParams();

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
