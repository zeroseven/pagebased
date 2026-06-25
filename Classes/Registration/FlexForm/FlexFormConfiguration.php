<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration\FlexForm;

use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Type\Exception;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Pagebased\Registration\Event\AddFlexFormEvent;

class FlexFormConfiguration
{
    /** @var FlexFormSheetConfiguration[] */
    protected array $sheets = [];

    public function __construct(protected string $table, protected string $type, protected string $field, protected ?string $position = null) {}

    public static function makeInstance(string $table, string $type, string $field, string $position = null): self
    {
        return GeneralUtility::makeInstance(self::class, $table, $type, $field, $position);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function getSheets(): array
    {
        return $this->sheets;
    }

    public function getSheet(string $key): ?FlexFormSheetConfiguration
    {
        return $this->sheets[$key] ?? null;
    }

    public function addSheet(FlexFormSheetConfiguration $flexFormSheetConfiguration): self
    {
        $this->sheets[$flexFormSheetConfiguration->getKey()] = $flexFormSheetConfiguration;

        return $this;
    }

    /** @throws Exception */
    public function addToTCA(): void
    {
        $config = [];

        GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new AddFlexFormEvent($this));

        foreach ($this->sheets as $sheet) {
            if ($sheet instanceof FlexFormSheetConfiguration) {
                $config['sheets'][$sheet->getKey()] = [
                    'ROOT' => [
                        'TCEforms' => [
                            'sheetTitle' => $sheet->getTitle(),
                        ],
                        'type' => 'array',
                        'el' => $sheet->getFields(),
                    ],
                ];
            } else {
                throw new Exception(sprintf('Argument is not instance of %s.', FlexFormSheetConfiguration::class), 1677576373);
            }
        }

        $dataStructure = GeneralUtility::makeInstance(FlexFormTools::class)->flexArray2Xml($config);

        // Register the data structure per content type. The mechanism differs between TYPO3 versions,
        // so branch on the shape of the default "ds":
        //  * v13 and earlier: pi_flexform ships an array "ds" plus a "ds_pointerField" (list_type,CType).
        //    Add the data structure under the matching "*,<CType>" pointer key.
        //  * v14: pi_flexform ships a single "ds" string and no "ds_pointerField", so a pointer is never
        //    matched. Bind the data structure to the CType via columnsOverrides instead.
        if (is_array($GLOBALS['TCA'][$this->table]['columns'][$this->field]['config']['ds'] ?? null)) {
            $GLOBALS['TCA'][$this->table]['columns'][$this->field]['config']['ds']['*,' . $this->type] = $dataStructure;
        } else {
            $GLOBALS['TCA'][$this->table]['types'][$this->type]['columnsOverrides'][$this->field]['config']['ds'] = $dataStructure;
        }

        // Add the flexForm TCA field to the content element
        ExtensionManagementUtility::addToAllTCAtypes($this->table, $this->field, $this->type, $this->position);
    }
}
