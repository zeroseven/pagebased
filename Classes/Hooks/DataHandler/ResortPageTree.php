<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Hooks\DataHandler;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Zeroseven\Pagebased\Domain\Model\AbstractPage;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Utility\ObjectUtility;
use Zeroseven\Pagebased\Utility\RootLineUtility;

class ResortPageTree
{
    protected function addNotification(int $parentPageUid, Registration $registration): void
    {
        $parentPage = BackendUtility::getRecord(AbstractPage::TABLE_NAME, $parentPageUid);

        $message = GeneralUtility::makeInstance(
            FlashMessage::class,
            LocalizationUtility::translate(
                'LLL:EXT:pagebased/Resources/Private/Language/locallang_be.xlf:notification.resortPagetree.description',
                'pagebased',
                [0 => BackendUtility::getRecordTitle(AbstractPage::TABLE_NAME, $parentPage)]
            ),
            LocalizationUtility::translate(
                'LLL:EXT:pagebased/Resources/Private/Language/locallang_be.xlf:notification.resortPagetree.title',
                'pagebased',
                [0 => $registration->getObject()->getTitle()]
            ), AbstractMessage::OK, true
        );

        $messageQueue = GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier();

        try {
            $messageQueue->enqueue($message);
        } catch (Exception $e) {
        }
    }

    protected function getUidList(QueryResultInterface $result): array
    {
        return array_map(static fn($object) => $object->getUid(), $result->toArray());
    }

    protected function updateSorting(int $parentPageUid, Registration $registration, DataHandler $dataHandler): void
    {
        $repository = $registration->getObject()->getRepositoryClass();

        if (array_key_first($registration->getObject()->getSorting()) !== $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['sortby']) {
            $demand = $registration->getObject()->getDemandClass()->setUidList(RootLineUtility::collectPagesBelow($parentPageUid, false, 1));

            $expectedOrdering = $repository->findByDemand($demand);
            $currentOrdering = $repository->findByDemand($demand->setOrderBy($GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['sortby']));

            if ($expectedOrdering->count() > 1 && $expectedOrdering->count() === $currentOrdering->count()) {
                $expectedUidList = $this->getUidList($expectedOrdering);
                $currentUidList = $this->getUidList($currentOrdering);

                if (implode('', $currentUidList) !== implode('', $expectedUidList)) {
                    $command = [];

                    foreach (array_reverse($expectedUidList) as $uid) {
                        $command[AbstractPage::TABLE_NAME][$uid]['move'] = $parentPageUid;
                    }

                    $dataHandler->start([], $command);
                    $dataHandler->process_cmdmap();

                    $this->addNotification($parentPageUid, $registration);
                    BackendUtility::setUpdateSignal('updatePageTree');
                }
            }
        }
    }

    /** @throws Exception */
    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        foreach ($dataHandler->datamap as $table => $uids) {
            if ($table === AbstractPage::TABLE_NAME) {
                $pidList = [];

                foreach ($uids as $uid => $data) {
                    MathUtility::canBeInterpretedAsInteger($uid)
                    && ($registration = ObjectUtility::isObject($uid, $data))
                    && ($pid = (int)($data['pid'] ?? BackendUtility::getRecord(AbstractPage::TABLE_NAME, $uid, 'pid')['pid']))
                    && ($pidList[$pid] = $registration);
                }

                foreach ($pidList as $pid => $registration) {
                    $this->updateSorting($pid, $registration, $dataHandler);
                }
            }
        }
    }

    public static function register(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = self::class;
    }
}
