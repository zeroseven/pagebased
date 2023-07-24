<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration\Event;

use Zeroseven\Pagebased\Registration\FlexForm\FlexFormConfiguration;

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
