<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration;

use ArrayAccess;
use ReflectionClass;
use ReflectionMethod;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Pagebased\Exception\RegistrationException;

abstract class AbstractRegistration implements RegistrationPropertyInterface, ArrayAccess
{
    protected string $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }

    public function getTitle(): string
    {
        if (str_starts_with($this->title, 'LLL:') && $languageService = $this->getLanguageService()) {
            return $languageService->sL($this->title);
        }

        return $this->title;
    }

    public function setTitle(string $title): RegistrationPropertyInterface
    {
        $this->title = trim($title);

        return $this;
    }

    /** Makes properties accessible in fluid template */
    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset);
    }

    /** Makes properties accessible in fluid template */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->{$offset} ??= null;
    }

    /** @throws RegistrationException */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $methods = self::getPublicMethods(get_class($this));
        $availableMethods = array_diff($methods, self::getPublicMethods(ArrayAccess::class));

        throw new RegistrationException('ArrayAccess is only for reading. Methods "offsetSet" and "offsetUnset" are not available. Please use other public methods instead: ' . implode(', ', array_map(static fn(string $method) => '"' . $method . '"', $availableMethods)));
    }

    /** @throws RegistrationException */
    public function offsetUnset(mixed $offset): void
    {
        $this->offsetSet($offset, null);
    }

    protected static function getPublicMethods(string $className): array
    {
        return array_map(static fn(ReflectionMethod $method) => $method->getName(),
            array_filter(GeneralUtility::makeInstance(ReflectionClass::class, $className)->getMethods(),
                static fn(ReflectionMethod $method) => !$method->isStatic() && $method->isPublic() && !str_starts_with($method->getName(), '__')
            ));
    }

    abstract public static function create(string $title): RegistrationPropertyInterface;
}
