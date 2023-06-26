<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

use TYPO3\CMS\Core\Localization\LanguageService;

abstract class AbstractRegistration implements RegistrationPropertyInterface
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
        if (str_starts_with($this->title, 'LLL:')) {
            if ($languageService = $this->getLanguageService()) {
                return $languageService->sL($this->title);
            }

            if (method_exists(get_class($this), 'getName')) {
                return $this->getName();
            }
        }

        return $this->title;
    }

    public function setTitle(string $title): RegistrationPropertyInterface
    {
        $this->title = trim($title);

        return $this;
    }

    abstract public static function create(string $title): RegistrationPropertyInterface;
}
