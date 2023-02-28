<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Zeroseven\Rampage\Backend\TCA\GroupFilter;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Domain\Model\Demand\AbstractDemand;
use Zeroseven\Rampage\Domain\Model\PageTypeInterface;
use Zeroseven\Rampage\Registration\PageObjectRegistration;
use Zeroseven\Rampage\Registration\PluginRegistration;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;

class AddTCAEvent
{
    protected function getPageType(PageObjectRegistration $pageObjectRegistration): ?int
    {
        if (is_subclass_of($pageObjectRegistration->getObjectClassName(), PageTypeInterface::class) && $pageType = $pageObjectRegistration->getObjectClassName()::getType()) {
            return $pageType;
        }

        return null;
    }

    protected function createPlugin(Registration $registration, PluginRegistration $pluginRegistration): string
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

    protected function createPageType(PageObjectRegistration $pageObjectRegistration): void
    {
        if ($pageType = $this->getPageType($pageObjectRegistration)) {

            // Add to type list
            if (($tcaTypeField = $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['type'] ?? null)) {
                ExtensionManagementUtility::addTcaSelectItem(
                    AbstractPage::TABLE_NAME,
                    $tcaTypeField,
                    [
                        $pageObjectRegistration->getTitle(),
                        $pageType,
                        $pageObjectRegistration->getIconIdentifier()
                    ],
                    '1',
                    'after'
                );
            }

            // Add basic fields
            $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['types'][$pageType]['showitem'] = $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['types'][1]['showitem'];

            // Add icon
            $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['typeicon_classes'][$pageType] = $pageObjectRegistration->getIconIdentifier();
            $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['ctrl']['typeicon_classes'][$pageType . '-hideinmenu'] = $pageObjectRegistration->getIconIdentifier(true);
        }
    }

    protected function addPageType(Registration $registration): void
    {
        if (($pageObject = $registration->getObject()) && $pageObject->isEnabled()) {
            $this->createPageType($pageObject);

            if ($pageType = $this->getPageType($pageObject)) {
                ExtensionManagementUtility::addToAllTCAtypes(AbstractPage::TABLE_NAME, sprintf('
                    --div--;%s,
                        _rampage_top,
                        _rampage_relations_to,
                        _rampage_relations_from
                ', $pageObject->getTitle()), (string)$pageType);

                // Configure relations
                $GLOBALS['TCA'][AbstractPage::TABLE_NAME]['types'][$pageType]['columnsOverrides']['_rampage_relations_to']['config'] = [
                    'filter' => [
                        [
                            'userFunc' => GroupFilter::class . '->filterTypes',
                            'parameters' => [
                                'allowed' => $pageType
                            ]
                        ]
                    ],
                    'suggestOptions' => [
                        'default' => [
                            'searchWholePhrase' => 1,
                            'addWhere' => ' AND ' . AbstractPage::TABLE_NAME . '.uid != ###THIS_UID###'
                        ],
                        AbstractPage::TABLE_NAME => [
                            'searchCondition' => 'doktype = ' . $pageType
                        ]
                    ],
                ];
            }
        }
    }

    protected function addPageCategory(Registration $registration): void
    {
        if (($pageCategory = $registration->getCategory()) && $pageCategory->isEnabled()) {
            $this->createPageType($pageCategory);
        }
    }

    protected function addListPlugin(Registration $registration): void
    {
        if ($registration->getListPlugin()->isEnabled()) {
            $this->createPlugin($registration, $registration->getListPlugin());
        }
    }

    protected function addFilterPlugin(Registration $registration): void
    {
        if ($registration->getFilterPlugin()->isEnabled()) {
            $cType = $this->createPlugin($registration, $registration->getFilterPlugin());
            $listCType = $registration->getListPlugin()->getCType($registration);

            if ($cType && $listCType) {
                $GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds']['*,' . $cType] = GeneralUtility::makeInstance(FlexFormTools::class)->flexArray2Xml([
                    'sheets' => [
                        'general' => [
                            'ROOT' => [
                                'TCEforms' => [
                                    'sheetTitle' => 'General'
                                ],
                                'type' => 'array',
                                'el' => [
                                    'settings.' . AbstractDemand::PARAMETER_CONTENT_ID => [
                                        'label' => 'Content ID',
                                        'config' => [
                                            'type' => 'group',
                                            'internal_type' => 'db',
                                            'foreign_table' => 'tt_content',
                                            'allowed' => 'tt_content',
                                            'size' => '1',
                                            'maxitems' => '1',
                                            'suggestOptions' => [
                                                'default' => [
                                                    'searchWholePhrase' => true
                                                ],
                                                'tt_content' => [
                                                    'searchCondition' => 'CType = "' . $listCType . '"'
                                                ]
                                            ],
                                            'filter' => [
                                                'userFunc' => GroupFilter::class . '->filterTypes',
                                                'parameters' => [
                                                    'allowed' => $listCType
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]);

                // Add the flexForm TCA field to the content element
                ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'pi_flexform', $cType, 'after:header');
            }
        }
    }

    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        foreach (RegistrationService::getRegistrations() as $registration) {
            $this->addPageType($registration);
            $this->addPageCategory($registration);
            $this->addListPlugin($registration);
            $this->addFilterPlugin($registration);
        }

        $event->setTca($GLOBALS['TCA']);
    }
}
