<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Event;

use JsonException;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Utility\ArrayPathUtility;

final class StructuredDataEvent
{
    protected Registration $registration;
    protected int $uid;
    protected array $row;
    protected ArrayPathUtility $properties;

    public function __construct(Registration $registration, int $uid, array $row)
    {
        $this->registration = $registration;
        $this->uid = $uid;
        $this->row = $row;
        $this->properties = ArrayPathUtility::create();
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
        return $this->properties->toArray();
    }

    public function getProperty(string $path): mixed
    {
        return $this->properties->get($path);
    }

    public function setProperty(string $path, mixed $value): self
    {
        $this->properties->set($path, $value);

        return $this;
    }

    public function addProperty(string $path, mixed $value, bool $force = null): self
    {
        $this->properties->add($path, $value, $force);

        return $this;
    }

    public function addPropertyType(string $path, array $value, string $type, bool $force = null): self
    {
        $this->properties->add($path, array_merge(['@type' => $type], $value), $force);

        return $this;
    }

    public function addProperties(array $properties, bool $force = null): self
    {
        foreach ($properties as $path => $value) {
            $this->properties->add($path, $value, $force);
        }

        return $this;
    }

    protected function createImageObjectType(FileReference $media = null): array
    {
        $imageService = GeneralUtility::makeInstance(ImageService::class);
        $processedImage = $imageService->applyProcessingInstructions($media, [
            'width' => '1920m',
            'height' => '1080m'
        ]);

        return array_merge([
            '@type' => 'ImageObject',
            'url' => $imageService->getImageUri($processedImage, true)
        ], ($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController && ($lastImageInfo = $GLOBALS['TSFE']->lastImageInfo ?? null) ? [
            'width' => $lastImageInfo[0],
            'height' => $lastImageInfo[1]
        ] : []);
    }

    protected function removeEmptyValues(array $array): array
    {
        return array_filter($array, fn($v) => !empty(is_array($v) ? $this->removeEmptyValues($v) : $v));
    }

    protected function parseProperties(array $array): array
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
        if (!$this->properties->isEmpty()) {
            try {
                return json_encode($this->parseProperties($this->properties->toArray()), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } catch (JsonException $e) {
            }
        }

        return null;
    }
}
