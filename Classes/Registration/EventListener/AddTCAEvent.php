<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Type\Exception as TypeException;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Zeroseven\Rampage\Backend\TCA\GroupFilter;
use Zeroseven\Rampage\Backend\TCA\ItemsProcFunc;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Domain\Model\Demand\AbstractDemand;
use Zeroseven\Rampage\Domain\Model\Demand\AbstractObjectDemand;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\AbstractRegistrationPluginProperty;
use Zeroseven\Rampage\Registration\FlexForm\FlexFormConfiguration;
use Zeroseven\Rampage\Registration\FlexForm\FlexFormSheetConfiguration;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;
use Zeroseven\Rampage\Utility\TCAUtility;

class AddTCAEvent
{
    protected function createPlugin(Registration $registration, AbstractRegistrationPluginProperty $pluginRegistration): string
    {
        $CType = $pluginRegistration->getCType($registration);

        // Add some default fields to the content elements by copy configuration of "header"
        $GLOBALS['TCA']['tt_content']['types'][$CType]['showitem'] = $GLOBALS['TCA']['tt_content']['types']['header']['showitem'];

        // Register plugin
        ExtensionUtility::registerPlugin(
            $registration->getExtensionName(),
            ucfirst($pluginRegistration->getType()),
            $pluginRegistration->getTitle(),
            $pluginRegistration->getIconIdentifier()
        );

        // Register icon
        $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$CType] = $pluginRegistration->getIconIdentifier();

        return $CType;
    }

    protected function addPageObject(Registration $registration): void
    {
        if ($objectRegistration = $registration->getObject()) {
            $displayCondition = TCAUtility::getObjectDisplayCondition($registration);

            TCAUtility::addDisplayCondition(AbstractPage::TABLE_NAME, 'rampage_date', $displayCondition);

            if ($objectRegistration->topEnabled()) {
                TCAUtility::addDisplayCondition(AbstractPage::TABLE_NAME, 'rampage_top', $displayCondition);
            }

            if ($objectRegistration->tagsEnabled()) {
                TCAUtility::addDisplayCondition(AbstractPage::TABLE_NAME, 'rampage_tags', $displayCondition);
            }

            if ($objectRegistration->topicsEnabled()) {
                TCAUtility::addDisplayCondition(AbstractPage::TABLE_NAME, 'rampage_topics', $displayCondition);
            }

            if ($objectRegistration->contactEnabled()) {
                TCAUtility::addDisplayCondition(AbstractPage::TABLE_NAME, 'rampage_contact', $displayCondition);
            }

            if ($objectRegistration->relationsEnabled()) {
                TCAUtility::addDisplayCondition(AbstractPage::TABLE_NAME, 'rampage_relations_to', $displayCondition);
                TCAUtility::addDisplayCondition(AbstractPage::TABLE_NAME, 'rampage_relations_from', $displayCondition);
            }
        }
    }

    protected function addPageCategory(Registration $registration): void
    {
        if (($categoryRegistration = $registration->getCategory()) && $pageType = $categoryRegistration->getDocumentType()) {

            // Add to type list
            if ($tcaTypeField = $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['type'] ?? null) {
                ExtensionManagementUtility::addTcaSelectItem(
                    AbstractPage::TABLE_NAME,
                    $tcaTypeField,
                    [
                        $categoryRegistration->getTitle(),
                        $pageType,
                        $categoryRegistration->getIconIdentifier()
                    ],
                    '1',
                    'after'
                );
            }

            // Add basic fields
            $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['types'][$pageType]['showitem'] = $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['types'][1]['showitem'];

            // Add icon
            $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['typeicon_classes'][$pageType] = $categoryRegistration->getIconIdentifier();
            $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['typeicon_classes'][$pageType . '-hideinmenu'] = $categoryRegistration->getIconIdentifier(true);

            // Add redirect field in page properties
            TCAUtility::addCategoryDisplayCondition($registration, 'rampage_redirect_category');
        }
    }

    /** @throws TypeException */
    protected function addListPlugin(Registration $registration): void
    {
        if ($registration->getListPlugin() && $cType = $this->createPlugin($registration, $registration->getListPlugin())) {
            $filterSheet = FlexFormSheetConfiguration::makeInstance('filter', 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.tab.filter');

            if ($typeField = $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['type'] ?? null) {
                $filterSheet->addField('settings.category', [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'minitems' => 0,
                    'maxitems' => 1,
                    'itemsProcFunc' => ItemsProcFunc::class . '->filterCategories',
                    'foreign_table' => 'pages',
                    'foreign_table_where' => sprintf(' AND pages.sys_language_uid <= 0 AND pages.%s = %d', $typeField, $registration->getCategory()->getDocumentType()),
                    'items' => [
                        ['LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.category.div.no_restrictions', '--div--'],
                        ['LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.category.all', 0],
                        ['LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.category.div.available', '--div--'],
                    ]
                ], 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.category');
            }

            if ($registration->getObject()->tagsEnabled()) {
                $filterSheet->addField('settings.tags', [
                    'type' => 'user',
                    'renderType' => 'rampageTags',
                    'placeholder' => 'ADD TAGS …',
                    'registrationIdentifier' => $registration->getIdentifier()
                ], 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.tags');
            }

            if ($registration->getObject()->topicsEnabled() && $topicPageIds = $registration->getObject()->getTopicPageIds()) {
                $filterSheet->addField('settings.topics', [
                    'type' => 'select',
                    'renderType' => 'selectCheckBox',
                    'foreign_table' => 'tx_rampage_domain_model_topic',
                    'MM' => 'tx_rampage_object_topic_mm',
                    'default' => 0,
                    'foreign_table_where' => sprintf(' AND {#tx_rampage_domain_model_topic}.{#pid} IN(%s) AND {#tx_rampage_domain_model_topic}.{#%s} < 1', implode(',', $topicPageIds), $GLOBALS['TCA']['tx_rampage_domain_model_topic']['ctrl']['languageField']),
                ], 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.topics');
            }

            if ($registration->getObject()->contactEnabled() && $contactPageIds = $registration->getObject()->getContactPageIds()) {
                $filterSheet->addField('settings.contact', [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'tx_rampage_domain_model_contact',
                    'default' => 0,
                    'foreign_table_where' => sprintf(' AND {#tx_rampage_domain_model_contact}.{#pid} IN(%s) AND {#tx_rampage_domain_model_contact}.{#%s} < 1', implode(',', $contactPageIds), $GLOBALS['TCA']['tx_rampage_domain_model_contact']['ctrl']['languageField']),
                    'items' => [
                        ['-', 0, 'actions-user']
                    ]
                ], 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.contact');
            }

            $optionsSheet = FlexFormSheetConfiguration::makeInstance('options', 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.tab.options');

            if ($registration->getObject()->topEnabled()) {
                $optionsSheet->addField('settings.' . AbstractObjectDemand::PROPERTY_TOP_MODE, [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'minitems' => 1,
                    'maxitems' => 1,
                    'items' => [
                        ['LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.topMode.0', 0],
                        ['LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.topMode.1', AbstractObjectDemand::TOP_MODE_FIRST],
                        ['LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.topMode.2', AbstractObjectDemand::TOP_MODE_ONLY]
                    ]
                ], 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.topMode');
            }

            $optionsSheet->addField('settings.' . AbstractDemand::PROPERTY_ORDER_BY, [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'minitems' => 1,
                'maxitems' => 1,
                'items' => [
                    ['LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.sorting.default', ''],
                    ['LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.sorting.date_desc', 'date_desc'],
                    ['LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.sorting.date_asc', 'date_asc'],
                    ['LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.sorting.title_asc', 'title_asc'],
                    ['LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.sorting.title_desc', 'title_desc']
                ]
            ], 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.sorting');

            $layoutSheet = FlexFormSheetConfiguration::makeInstance('layout', 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.tab.layout')
                ->addField('settings.itemsPerStage', [
                    'placeholder' => '6',
                    'type' => 'input',
                    'eval' => 'trim,is_in',
                    'is_in' => ',0123456789'
                ], 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.itemsPerStage')
                ->addField('settings.maxStages', [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'minitems' => 1,
                    'maxitems' => 1,
                    'items' => [
                        ['LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.maxStages.0', 0],
                        ['1', 1],
                        ['2', 2],
                        ['3', 3],
                        ['4', 4],
                        ['5', 5]
                    ]
                ], 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.maxStages');

            FlexFormConfiguration::makeInstance('tt_content', $cType, 'pi_flexform', 'after:header')
                ->addSheet($filterSheet)
                ->addSheet($optionsSheet)
                ->addSheet($layoutSheet)
                ->addToTCA();
        }
    }

    /** @throws TypeException */
    protected function addFilterPlugin(Registration $registration): void
    {
        if ($registration->getFilterPlugin()) {
            $cType = $this->createPlugin($registration, $registration->getFilterPlugin());
            $listCType = $registration->getFilterPlugin()->getCType($registration);

            // FlexForm configuration
            if ($cType && $listCType) {
                $table = 'tt_content';

                $generalSheet = FlexFormSheetConfiguration::makeInstance('general', 'General setttings')
                    ->addField('settings.' . AbstractObjectDemand::PROPERTY_CONTENT_ID, [
                        'type' => 'group',
                        'internal_type' => 'db',
                        'foreign_table' => $table,
                        'allowed' => $table,
                        'size' => '1',
                        'maxitems' => '1',
                        'suggestOptions' => [
                            'default' => [
                                'searchWholePhrase' => true
                            ],
                            $table => [
                                'searchCondition' => 'CType = "' . $listCType . '"'
                            ]
                        ],
                        'filter' => [
                            'userFunc' => GroupFilter::class . '->filterTypes',
                            'parameters' => [
                                'allowed' => $listCType
                            ]
                        ]
                    ], 'LLL:EXT:rampage/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.contentId');

                FlexFormConfiguration::makeInstance($table, $cType, 'pi_flexform', 'after:header')
                    ->addSheet($generalSheet)
                    ->addToTCA();
            }
        }
    }

    /** @throws RegistrationException | TypeException */
    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        foreach (RegistrationService::getRegistrations() as $registration) {
            $this->addPageObject($registration);
            $this->addPageCategory($registration);
            $this->addListPlugin($registration);
            $this->addFilterPlugin($registration);
        }

        $event->setTca($GLOBALS['TCA']);
    }
}
