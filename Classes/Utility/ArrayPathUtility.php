<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Utility;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Get/set array properties by path.
 * Example: 'view.templateRootPaths.0' returns the value of $data['view']['templateRootPaths'][0]
 */
class ArrayPathUtility
{
    protected const PATH_DIVIDER = '.';

    protected array $data;
    private PropertyAccessor $propertyAccessor;

    public function __construct(array $array = null)
    {
        $this->data = $array ?? [];
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->disableMagicGet()
            ->disableMagicSet()
            ->disableMagicCall()
            ->disableMagicMethods()
            ->getPropertyAccessor();
    }

    protected function convertPathToPropertyPath(string $path): string
    {
        return implode('', array_map(static fn($property) => '[' . $property . ']', explode(self::PATH_DIVIDER, trim($path, self::PATH_DIVIDER))));
    }

    public function get(string $propertyPath = null): mixed
    {
        if($propertyPath === null) {
            return $this->data;
        }

        if (($path = $this->convertPathToPropertyPath($propertyPath)) && $this->propertyAccessor->isReadable($this->data, $path)) {
            return $this->propertyAccessor->getValue($this->data, $path);
        }

        return null;
    }

    public function set(string $propertyPath, mixed $value): self
    {
        if (($path = $this->convertPathToPropertyPath($propertyPath)) && $this->propertyAccessor->isWritable($this->data, $path)) {
            $this->propertyAccessor->setValue($this->data, $path, $value);
        }

        return $this;
    }

    public function add(string $propertyPath, mixed $value, bool $force = null): self
    {
        if ($force || !$this->get($propertyPath)) {
            $this->set($propertyPath, $value);
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public static function create(...$arguments): self
    {
        return GeneralUtility::makeInstance(self::class, ...$arguments);
    }

    public static function getPath(array $data, string $path = null): mixed
    {
        return self::create($data)->get($path);
    }

    public static function setPath(array $data, string $path, mixed $value): array
    {
        return self::create($data)->set($path, $value)->toArray();
    }
}
