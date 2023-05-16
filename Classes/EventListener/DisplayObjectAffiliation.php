<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\EventListener;

use TYPO3\CMS\Backend\Controller\Event\BeforeFormEnginePageInitializedEvent;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Zeroseven\Rampage\Domain\Model\PageObjectInterface;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Utility\ObjectUtility;
use Zeroseven\Rampage\Utility\SettingsUtility;

class DisplayObjectAffiliation
{
    protected function showMessage(Registration $registration, PageObjectInterface $parentObject): void
    {
        $message = LocalizationUtility::translate('LLL:EXT:rampage/Resources/Private/Language/locallang_be.xlf:notification.objectAffiliation.description',
            SettingsUtility::EXTENSION_NAME, [$registration->getObject()->getName(), $parentObject->getTitle()]);

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

        ($editConfiguration = $parsedBody['edit'] ?? $queryParams['edit'] ?? null)
        && ($table = array_key_first($editConfiguration))
        && ($uid = (int)(array_key_first($editConfiguration[$table] ?? [])))
        && ($registration = ObjectUtility::isChildObject($uid))
        && ($parentObject = $registration->getObject()->getRepositoryClass()->findByUid($uid)->getParentObject())
        && $this->showMessage($registration, $parentObject);
    }
}
