<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\FlexForm;

use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Type\Exception;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Event\AddFlexFormEvent;
use Zeroseven\Rampage\Event\StoreRegistrationEvent;

class FlexFormConfiguration
{
    protected string $table;
    protected string $type;
    protected string $field;
    protected ?string $position;

    /** @var FlexFormSheetConfiguration[] */
    protected array $sheets = [];

    public function __construct(string $table, string $type, string $field, string $position = null)
    {
        $this->table = $table;
        $this->type = $type;
        $this->field = $field;
        $this->position = $position;
    }

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

    public function addSheet(FlexFormSheetConfiguration $sheet): self
    {
        $this->sheets[$sheet->getKey()] = $sheet;

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
                            'sheetTitle' => $sheet->getTitle()
                        ],
                        'type' => 'array',
                        'el' => $sheet->getFields()
                    ]
                ];
            } else {
                throw new Exception(sprintf('Argument is not instance of %s.', FlexFormSheetConfiguration::class), 1677576373);
            }
        }

        $GLOBALS['TCA'][$this->table]['columns'][$this->field]['config']['ds']['*,' . $this->type] = GeneralUtility::makeInstance(FlexFormTools::class)->flexArray2Xml($config);

        // Add the flexForm TCA field to the content element
        ExtensionManagementUtility::addToAllTCAtypes($this->table, $this->field, $this->type, $this->position);
    }
}
