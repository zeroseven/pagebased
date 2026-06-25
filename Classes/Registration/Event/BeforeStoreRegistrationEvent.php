<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration\Event;

use Zeroseven\Pagebased\Registration\Registration;

final class BeforeStoreRegistrationEvent
{
    public function __construct(private Registration $registration) {}

    public function getRegistration(): Registration
    {
        return $this->registration;
    }

    public function setRegistration(Registration $registration): void
    {
        $this->registration = $registration;
    }
}
