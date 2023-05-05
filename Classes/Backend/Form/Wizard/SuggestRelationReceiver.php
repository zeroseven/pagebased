<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Backend\Form\Wizard;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Form\Wizard\SuggestWizardDefaultReceiver;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Utility\IdentifierUtility;
use Zeroseven\Rampage\Utility\RootLineUtility;

class SuggestRelationReceiver extends SuggestWizardDefaultReceiver
{
    public function __construct($table, $config)
    {
        parent::__construct($table, $config);

        if (($GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) && ($parsedBody = $GLOBALS['TYPO3_REQUEST']->getParsedBody()) && $uid = (int)($parsedBody['uid'] ?? 0)) {
            $objectRegistration = GeneralUtility::makeInstance(IdentifierUtility::class, $uid, $table)->getObjectRegistration();

            if ($objectRegistration) {
                $this->queryBuilder->andWhere($this->queryBuilder->expr()->eq(IdentifierUtility::OBJECT_FIELD_NAME, $this->queryBuilder->createNamedParameter($objectRegistration->getClassName())));

                if ($rootPage = RootLineUtility::getRootPage($uid)) {
                    $this->queryBuilder->andWhere($this->queryBuilder->expr()->eq(IdentifierUtility::SITE_FIELD_NAME, $rootPage));
                }
            }
        }
    }
}
