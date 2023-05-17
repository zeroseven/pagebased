<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Event;

use Zeroseven\Rampage\Registration\Registration;

final class AssignTemplateVariablesEvent
{
    protected array $variables;
    protected Registration $registration;
    protected string $controllerAction;

    public function __construct(array $variables, Registration $registration, string $controllerAction)
    {
        $this->variables = $variables;
        $this->registration = $registration;
        $this->controllerAction = $controllerAction;
    }

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

    public function getControllerAction(): string
    {
        return $this->controllerAction;
    }
}
