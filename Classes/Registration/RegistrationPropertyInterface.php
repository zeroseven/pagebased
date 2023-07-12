<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration;

interface RegistrationPropertyInterface
{
    public function getTitle(): string;

    public function setTitle(string $title): self;
}
