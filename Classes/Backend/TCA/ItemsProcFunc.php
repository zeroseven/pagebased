<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Backend\TCA;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Utility\RootLineUtility;

class ItemsProcFunc
{
    protected function getPageUid(array $config): int
    {
        return GeneralUtility::_GP('id') ?: $config['flexParentDatabaseRow']['pid'] ?? 0;
    }

    protected function getRootPageUid(array $config): int
    {
        if ($currentUid = $this->getPageUid($config)) {
            return RootLineUtility::getRootPage($currentUid);
        }

        return 0;
    }

    public function filterCategories(array &$PA): void
    {
        if ($rootPages = $this->getRootPageUid($PA)) {
            $queryConstraints = $PA['config']['foreign_table_where'] ?? '';
            $localPages = RootLineUtility::collectPagesBelow($rootPages);
            $parentPages = RootLineUtility::collectPagesAbove($this->getPageUid($PA));
            $closestCategoryUid = 0;

            // Search for the closest category in rootline
            foreach (array_keys($parentPages) as $key) {
                if ($closestCategoryUid === 0 && BackendUtility::getRecord(AbstractPage::TABLE_NAME, $key, 'uid', $queryConstraints)) {
                    $closestCategoryUid = $key;
                }
            }

            // Remove categories of other page trees or the closest category page
            $localPageIds = array_keys($localPages);
            foreach ($PA['items'] ?? [] as $key => $item) {
                if ((int)($value = $item[1] ?? 0) && $value !== '--div--' && (!in_array($value, $localPageIds, true) || $value === $closestCategoryUid)) {
                    unset($PA['items'][$key]);
                }
            }

            // Add closes category
            if ($closestCategoryUid) {
                array_unshift($PA['items'], ['SUGGESTED CATEGORY', '--div--'], [$localPages[$closestCategoryUid]['title'] ?? '', $closestCategoryUid]);
            }
        }
    }
}
