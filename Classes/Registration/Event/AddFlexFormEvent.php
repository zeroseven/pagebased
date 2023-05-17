<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\Event;

use Zeroseven\Rampage\Registration\FlexForm\FlexFormConfiguration;

final class AddFlexFormEvent
{
    protected FlexFormConfiguration $flexFormConfiguration;

    public function __construct(FlexFormConfiguration $flexFormConfiguration)
    {
        $this->flexFormConfiguration = $flexFormConfiguration;
    }

    public function getFlexFormConfiguration(): FlexFormConfiguration
    {
        return $this->flexFormConfiguration;
    }
}
