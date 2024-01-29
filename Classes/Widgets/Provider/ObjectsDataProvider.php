<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Widgets\Provider;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\WidgetApi;
use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;
use Zeroseven\Pagebased\Utility\DetectionUtility;

class ObjectsDataProvider implements ChartDataProviderInterface
{
    private function countObjects(Registration $registration): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)?->getQueryBuilderForTable('pages');
        $queryBuilder->addSelectLiteral($queryBuilder->expr()->count('uid', 'count'))
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(DetectionUtility::REGISTRATION_FIELD_NAME, $queryBuilder->createNamedParameter($registration->getIdentifier())),
                $queryBuilder->expr()->eq(DetectionUtility::CHILD_OBJECT_FIELD_NAME, 0),
                $queryBuilder->expr()->neq('doktype', $queryBuilder->createNamedParameter($registration->getCategory()->getDocumentType())),
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq('hidden', 0)
            );

        try {
            return (int)$queryBuilder->executeQuery()->fetchOne();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getChartData(): array
    {
        $data = [
            'labels' => [],
            'datasets' => [
                [
                    'backgroundColor' => WidgetApi::getDefaultChartColors(),
                    'data' => []
                ]
            ]
        ];

        foreach (RegistrationService::getRegistrations() as $registration) {
            $data['labels'][] = $registration->getObject()->getName();
            $data['datasets'][0]['data'][] = $this->countObjects($registration);
        }

        return $data;
    }
}
