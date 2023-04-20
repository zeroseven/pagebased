<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Hooks\DataHandler;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;

class ResortPageTree
{
    protected function getUidList(QueryResultInterface $result): array
    {
        return array_map(static fn($object) => $object->getUid(), $result->toArray());
    }

    protected function updateSorting(int $parentPageUid, Registration $registration, DataHandler $dataHandler): bool
    {
        $repository = $registration->getObject()->getRepositoryClass();
        $demand = $registration->getObject()->getDemandClass()->setCategory($parentPageUid);

        $expectedOrdering = $repository->findByDemand($demand);
        $currentOrdering = $repository->findByDemand($demand->setOrderBy('sorting'));

        if ($expectedOrdering->count() > 1 && $expectedOrdering->count() === $currentOrdering->count()) {
            $expectedUidList = $this->getUidList($expectedOrdering);
            $currentUidList = $this->getUidList($currentOrdering);

            if (implode('', $currentUidList) !== implode('', $expectedUidList)) {

                // Create command to sort the pages
                $command = [];

                foreach (array_reverse($expectedUidList) as $uid) {
                    $command[AbstractPage::TABLE_NAME][$uid]['move'] = $parentPageUid;
                }

                $dataHandler->start([], $command);
                $dataHandler->process_cmdmap();

                return true;
            }
        }

        return false;
    }

    /** @throws RegistrationException */
    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        foreach ($dataHandler->datamap as $table => $uids) {
            if ($table === AbstractPage::TABLE_NAME) {

                // Slice the first three …
                foreach (array_slice($uids, 0, 3, true) as $uid => $data) {
                    $pid = (int)($data['pid'] ?? BackendUtility::getRecord(AbstractPage::TABLE_NAME, $uid, 'pid')['pid']);
                    $parentPage = BackendUtility::getRecord(AbstractPage::TABLE_NAME, $pid);

                    if (($documentType = (int)($parentPage['doktype'] ?? 0)) && $registration = RegistrationService::getRegistrationByCategoryDocumentType($documentType)) {
                        if ($this->updateSorting($pid, $registration, $dataHandler)) {
                            BackendUtility::setUpdateSignal('updatePageTree');

                            $message = GeneralUtility::makeInstance(
                                FlashMessage::class,
                                LocalizationUtility::translate(
                                    'LLL:EXT:rampage/Resources/Private/Language/locallang_be.xlf:notification.resortPagetree.description',
                                    'rampage',
                                    [0 => BackendUtility::getRecordTitle(AbstractPage::TABLE_NAME, $parentPage)]
                                ),
                                LocalizationUtility::translate(
                                    'LLL:EXT:rampage/Resources/Private/Language/locallang_be.xlf:notification.resortPagetree.title',
                                    'rampage',
                                    [0 => $registration->getObject()->getTitle()]
                                ), AbstractMessage::INFO, true
                            );

                            $messageQueue = GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier();
                            $messageQueue->enqueue($message);
                        }
                    }
                }
            }
        }
    }

    public static function register(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = self::class;
    }
}
