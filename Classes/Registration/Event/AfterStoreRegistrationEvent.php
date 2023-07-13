<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration\Event;

use Zeroseven\Pagebased\Registration\Registration;

final class AfterStoreRegistrationEvent
{
    private Registration $registration;

    public function __construct(Registration $registration)
    {
        $this->registration = $registration;
    }

    public function getRegistration(): Registration
    {
        return $this->registration;
    }

    public function setRegistration(Registration $registration): void
    {
        $this->registration = $registration;
    }
}
