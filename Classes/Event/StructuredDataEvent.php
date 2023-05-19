<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Event;

use JsonException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Zeroseven\Rampage\Registration\Registration;

final class StructuredDataEvent
{
    protected Registration $registration;
    protected int $uid;
    protected array $row;
    protected array $properties = [];
    protected PropertyAccessor $propertyAccessor;

    public function __construct(Registration $registration, int $uid, array $row)
    {
        $this->registration = $registration;
        $this->uid = $uid;
        $this->row = $row;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->disableMagicGet()
            ->disableMagicSet()
            ->disableMagicCall()
            ->disableMagicMethods()
            ->getPropertyAccessor();
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
        return $this->properties;
    }

    /**
     * @param string $propertyPath Example: 'author.image.url'
     * @return mixed
     */
    public function getProperty(string $propertyPath): mixed
    {
        if (($path = $this->convertPathToPropertyPath($propertyPath)) && $this->propertyAccessor->isReadable($this->properties, $path)) {
            return $this->propertyAccessor->getValue($this->properties, $path);
        }

        return null;
    }

    public function addProperty(string $propertyPath, mixed $value, bool $force = null): self
    {
        if ($force || !$this->getProperty($propertyPath)) {
            $this->setProperty($propertyPath, $value);
        }

        return $this;
    }

    public function addProperties(array $properties, bool $force = null): self
    {
        foreach ($properties as $propertyPath => $value) {
            $this->addProperty($propertyPath, $value, $force);
        }

        return $this;
    }

    /**
     * @param string $propertyPath Example: 'author.image.url'
     * @param mixed $value Example: 'https://www.example.com/image.jpg'
     * @return $this
     */
    public function setProperty(string $propertyPath, mixed $value): self
    {
        if (($path = $this->convertPathToPropertyPath($propertyPath)) && $this->propertyAccessor->isWritable($this->properties, $path)) {
            $this->propertyAccessor->setValue($this->properties, $path, $value);
        }

        return $this;
    }

    protected function convertPathToPropertyPath(string $path): string
    {
        return implode('', array_map(static fn($property) => '[' . $property . ']', explode('.', $path)));
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
        if (empty($array['@type'])) {
            return [];
        }

        // Create output
        $output = [];

        // Remove empty values
        $data = $this->removeEmptyValues($array);

        // Loop through array (recursive)
        foreach ($data as $key => $value) {
            if ($value instanceof FileReference) {
                $value = $this->createImageObjectType($value);
            }

            $output[$key] = is_array($value) ? $this->parseProperties($value) : $value;
        }

        return $output;
    }

    public function parse(): ?string
    {
        if ($this->properties) {
            try {
                return json_encode($this->parseProperties($this->properties), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } catch (JsonException $e) {
            }
        }

        return null;
    }
}
