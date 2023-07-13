<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Backend\Form\Wizard;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Form\Wizard\SuggestWizardDefaultReceiver;
use Zeroseven\Pagebased\Domain\Model\AbstractPage;
use Zeroseven\Pagebased\Utility\DetectionUtility;
use Zeroseven\Pagebased\Utility\ObjectUtility;
use Zeroseven\Pagebased\Utility\RootLineUtility;

class SuggestRelationReceiver extends SuggestWizardDefaultReceiver
{
    public function __construct($table, $config)
    {
        parent::__construct($table, $config);

        if (
            $table === AbstractPage::TABLE_NAME
            && $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface
            && ($parsedBody = $GLOBALS['TYPO3_REQUEST']->getParsedBody())
            && ($uid = (int)($parsedBody['uid'] ?? 0))
            && ($registration = ObjectUtility::isObject($uid))
        ) {
            $this->queryBuilder->andWhere($this->queryBuilder->expr()->eq(DetectionUtility::REGISTRATION_FIELD_NAME, $this->queryBuilder->createNamedParameter($registration->getIdentifier())));

            if ($rootPage = RootLineUtility::getRootPage($uid)) {
                $this->queryBuilder->andWhere($this->queryBuilder->expr()->eq(DetectionUtility::SITE_FIELD_NAME, $rootPage));
            }
        }
    }
}
