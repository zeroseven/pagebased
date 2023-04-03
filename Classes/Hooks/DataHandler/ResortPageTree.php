<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Hooks\DataHandler;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;

class ResortPageTree
{
    /** @throws RegistrationException */
    protected function getObjectDocumentTypes(): array
    {
        $objectDocumentTypes = [];

        foreach (RegistrationService::getRegistrations() as $registration) {
            $objectDocumentTypes[$registration->getObject()->getObjectType()] = $registration;
        }

        return $objectDocumentTypes;
    }

    protected function getUidList(QueryResultInterface $result): array
    {
        return array_map(static fn($object) => $object->getUid(), $result->toArray());
    }

    protected function updateSorting(int $parentPageUid, Registration $registration, DataHandler $dataHandler): void
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

                BackendUtility::setUpdateSignal('updatePageTree');
            }
        }
    }

    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        foreach ($dataHandler->datamap as $table => $uids) {
            if ($table === AbstractPage::TABLE_NAME) {
                $objectDocumentTypes = $this->getObjectDocumentTypes();

                // Slice the first three …
                foreach (array_slice($uids, 0, 3, true) as $uid => $data) {
                    if (($documentType = (int)($data['doktype'] ?? 0)) && $registration = $objectDocumentTypes[$documentType] ?? null) {

                        // Get the parent page id
                        $pid = (int)($data['pid'] ?? BackendUtility::getRecord(AbstractPage::TABLE_NAME, $uid, 'pid')['pid']);

                        $this->updateSorting($pid, $registration, $dataHandler);
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
