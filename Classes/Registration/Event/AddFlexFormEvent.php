<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration\Event;

use Zeroseven\Pagebased\Registration\FlexForm\FlexFormConfiguration;

final readonly class AddFlexFormEvent
{
    public function __construct(private FlexFormConfiguration $flexFormConfiguration) {}

    public function getFlexFormConfiguration(): FlexFormConfiguration
    {
        return $this->flexFormConfiguration;
    }
}
