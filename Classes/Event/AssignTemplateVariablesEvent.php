<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Event;

use Zeroseven\Pagebased\Registration\Registration;

final class AssignTemplateVariablesEvent
{
    public function __construct(private array $variables, private readonly Registration $registration, private readonly ?string $controllerAction = null) {}

    public function addVariable(string $key, mixed $value, bool $force = null): self
    {
        if ($force || !isset($this->variables[$key])) {
            $this->variables[$key] = $value;
        }

        return $this;
    }

    public function getVariable(string $key): mixed
    {
        return $this->variables[$key] ?? null;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function getRegistration(): Registration
    {
        return $this->registration;
    }

    public function getControllerAction(): ?string
    {
        return $this->controllerAction;
    }
}
