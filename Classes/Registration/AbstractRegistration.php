<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Pagebased\Exception\RegistrationException;

abstract class AbstractRegistration implements RegistrationPropertyInterface, \ArrayAccess
{
    public function __construct(protected string $title) {}

    protected function translate(string $string): string
    {
        return str_starts_with($string, 'LLL:')
        && ($GLOBALS['LANG'] ?? null) instanceof LanguageService
            ? $GLOBALS['LANG']->sL($string)
            : $string;
    }

    public function getTitle(): string
    {
        return $this->translate($this->title);
    }

    public function setTitle(string $title): RegistrationPropertyInterface
    {
        $this->title = $title;

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
        $methods = self::getPublicMethods(static::class);
        $availableMethods = array_diff($methods, self::getPublicMethods(\ArrayAccess::class));

        throw new RegistrationException('ArrayAccess is only for reading. Methods "offsetSet" and "offsetUnset" are not available. Please use other public methods instead: ' . implode(', ', array_map(static fn(string $method): string => '"' . $method . '"', $availableMethods)), 1337713225);
    }

    /** @throws RegistrationException */
    public function offsetUnset(mixed $offset): void
    {
        $this->offsetSet($offset, null);
    }

    protected static function getPublicMethods(string $className): array
    {
        return array_map(
            static fn(\ReflectionMethod $reflectionMethod): string => $reflectionMethod->getName(),
            array_filter(
                GeneralUtility::makeInstance(\ReflectionClass::class, $className)->getMethods(),
                static fn(\ReflectionMethod $reflectionMethod): bool => !$reflectionMethod->isStatic() && $reflectionMethod->isPublic() && !str_starts_with($reflectionMethod->getName(), '__')
            )
        );
    }

    abstract public static function create(string $title): RegistrationPropertyInterface;
}
