<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\EventListener;

use TYPO3\CMS\Backend\Controller\Event\BeforeFormEnginePageInitializedEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\BadConstraintException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use Zeroseven\Pagebased\Domain\Model\AbstractPage;
use Zeroseven\Pagebased\Utility\DetectionUtility;
use Zeroseven\Pagebased\Utility\ObjectUtility;
use Zeroseven\Pagebased\Utility\SettingsUtility;

class DisplayObjectInformation
{
    protected function showMessage(string $message, int $uid = null, string $title = null): void
    {
        try {
            $uid && GeneralUtility::makeInstance(Context::class)->getAspect('backend.user')->isAdmin()
            && ($registrationIdentifier = BackendUtility::getRecord(AbstractPage::TABLE_NAME, $uid, DetectionUtility::REGISTRATION_FIELD_NAME)[DetectionUtility::REGISTRATION_FIELD_NAME] ?? null)
            && $message .= ' [identifier: ' . $registrationIdentifier . ']';
        } catch (AspectNotFoundException $e) {
        }

        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $title ?? '', ContextualFeedbackSeverity::INFO);

        try {
            $messageQueue = GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier();
            $messageQueue->enqueue($flashMessage);
        } catch (Exception $e) {
        }
    }

    protected function translate(string $key, array $arguments = null, string $fileName = null): string
    {
        return LocalizationUtility::translate('LLL:EXT:pagebased/Resources/Private/Language/' . ($fileName ?? 'locallang_be.xlf') . ':' . $key,
            'pagebased', $arguments ?? []) ?? $key;
    }

    protected function isChildObject(int $uid): bool
    {
        if (
            ($registration = ObjectUtility::isChildObject($uid))
            && ($parentObject = $registration->getObject()->getRepositoryClass()->findParentObject($uid))
        ) {
            $this->showMessage($this->translate('notification.objectAffiliation.description', [$registration->getObject()->getTitle(), $parentObject->getTitle()]), $uid);
            return true;
        }

        return false;
    }

    protected function isObject(int $uid): bool
    {
        if ($registration = ObjectUtility::isObject($uid)) {
            $this->showMessage($this->translate('notification.object.description', [
                $registration->getObject()->getTitle(),
                $this->translate('pages.tab.pagebased_settings', null, 'locallang_db.xlf'),
            ]), $uid);

            return true;
        }

        return false;
    }

    protected function isCategory(int $uid): bool
    {
        if ($registration = ObjectUtility::isCategory($uid)) {
            $demand = $registration->getObject()->getDemandClass();

            try {
                $count = $registration->getObject()->getRepositoryClass()->findByDemand($demand->setCategory($uid))->count();
            } catch (BadConstraintException $e) {
                $count = 0;
            }

            $this->showMessage($this->translate('notification.category.description', [$count, $registration->getObject()->getTitle()]), $uid);

            return true;
        }

        return false;
    }

    public function __invoke(BeforeFormEnginePageInitializedEvent $event): void
    {
        $parsedBody = $event->getRequest()->getParsedBody();
        $queryParams = $event->getRequest()->getQueryParams();

        ($editConfiguration = $parsedBody['edit'] ?? $queryParams['edit'] ?? null)
        && ($table = array_key_first($editConfiguration)) === AbstractPage::TABLE_NAME
        && ($uid = (int)(array_key_first($editConfiguration[$table] ?? [])))
        && ($this->isChildObject($uid) || $this->isObject($uid) || $this->isCategory($uid));
    }
}
