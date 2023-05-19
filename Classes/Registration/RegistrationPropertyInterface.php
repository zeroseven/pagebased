<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration;

interface RegistrationPropertyInterface
{
    public function getTitle(): string;

    public function setTitle(string $title): self;
}
