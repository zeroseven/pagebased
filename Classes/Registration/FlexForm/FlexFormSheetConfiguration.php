<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration\FlexForm;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class FlexFormSheetConfiguration
{
    protected string $key;
    protected string $title;
    protected array $fields = [];

    public function __construct(string $key, string $title = null)
    {
        $this->key = $key;
        $this->title = $title ?? $key;
    }

    public static function makeInstance(string $key, string $title = null): self
    {
        return GeneralUtility::makeInstance(self::class, $key, $title);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function addField(string $fieldKey, array $fieldConfig, string $fieldTitle = null, array $options = null): self
    {
        $this->fields[$fieldKey] = array_merge($options ?? [], [
            'label' => $fieldTitle ?? $fieldKey,
            'config' => $fieldConfig
        ]);

        return $this;
    }
}
