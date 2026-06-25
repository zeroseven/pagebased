<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Event;

use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;
use TYPO3\CMS\Extbase\Service\ImageService;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Utility\ArrayPathUtility;

final readonly class StructuredDataEvent
{
    private ArrayPathUtility $arrayPathUtility;

    public function __construct(private Registration $registration, private int $uid, private array $row)
    {
        $this->arrayPathUtility = ArrayPathUtility::create();
    }

    public function getRegistration(): Registration
    {
        return $this->registration;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getRow(): array
    {
        return $this->row;
    }

    public function getProperties(): array
    {
        return $this->arrayPathUtility->toArray();
    }

    public function getProperty(string $path): mixed
    {
        return $this->arrayPathUtility->get($path);
    }

    public function setProperty(string $path, mixed $value): self
    {
        $this->arrayPathUtility->set($path, $value);

        return $this;
    }

    public function addProperty(string $path, mixed $value, bool $force = null): self
    {
        $this->arrayPathUtility->add($path, $value, $force);

        return $this;
    }

    public function addPropertyType(string $path, array $value, string $type, bool $force = null): self
    {
        $this->arrayPathUtility->add($path, array_merge(['@type' => $type], $value), $force);

        return $this;
    }

    public function addProperties(array $properties, bool $force = null): self
    {
        foreach ($properties as $path => $value) {
            $this->arrayPathUtility->add($path, $value, $force);
        }

        return $this;
    }

    private function createImageObjectType(FileReference $fileReference = null): array
    {
        $imageService = GeneralUtility::makeInstance(ImageService::class);
        $processedFile = $imageService->applyProcessingInstructions($fileReference, [
            'width' => '1920m',
            'height' => '1080m',
        ]);

        return [
            '@type' => 'ImageObject',
            'url' => $imageService->getImageUri($processedFile, true),
            'width' => $processedFile->getProperty('width'),
            'height' => $processedFile->getProperty('height'),
        ];
    }

    private function removeEmptyValues(array $array): array
    {
        return array_filter($array, fn($v): bool => !empty(is_array($v) ? $this->removeEmptyValues($v) : $v));
    }

    private function parseProperties(array $array): array
    {
        // Create output
        $output = [];

        // Remove empty values
        $data = $this->removeEmptyValues($array);

        // Loop through array (recursive)
        foreach ($data as $key => $value) {
            if ($value instanceof FileReference) {
                $value = $this->createImageObjectType($value);
            }

            if ($value instanceof ExtbaseFileReference) {
                $value = $this->createImageObjectType($value->getOriginalResource());
            }

            $output[$key] = is_array($value) ? $this->parseProperties($value) : $value;
        }

        return $output;
    }

    public function parse(): ?string
    {
        if (!$this->arrayPathUtility->isEmpty()) {
            try {
                return json_encode($this->parseProperties($this->arrayPathUtility->toArray()), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } catch (\JsonException) {
            }
        }

        return null;
    }
}
