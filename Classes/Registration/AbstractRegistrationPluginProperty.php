<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Registration;

use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\Pagebased\Exception\RegistrationException;
use Zeroseven\Pagebased\Exception\TypeException;
use Zeroseven\Pagebased\Utility\CastUtility;

abstract class AbstractRegistrationPluginProperty extends AbstractRegistration
{
    protected string $type;
    protected ?string $description = null;
    protected ?string $iconIdentifier = null;
    protected array $layouts = [];

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->translate($this->description);
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getIconIdentifier(): string
    {
        return $this->iconIdentifier ?? 'content-text';
    }

    public function setIconIdentifier(string $iconIdentifier): self
    {
        $this->iconIdentifier = $iconIdentifier;
        return $this;
    }

    /** @throws RegistrationException */
    public function addLayout(string $layout, string $label = null): self
    {
        if (empty($layout)) {
            throw new RegistrationException('The layout identifier must not be empty.', 1602685723);
        }

        $this->layouts[preg_replace('/\s/', '', $layout)] = empty($label) ? $layout : $this->translate($label);
        return $this;
    }

    /** @throws RegistrationException | TypeException */
    public function addLayouts(mixed $input): self
    {
        foreach (CastUtility::array($input) as $layout => $label) {
            $this->addLayout(MathUtility::canBeInterpretedAsInteger($layout) ? $label : $layout, (string)$label);
        }

        return $this;
    }

    public function getLayouts(): array
    {
        return $this->layouts;
    }

    public function getCType(Registration $registration): string
    {
        return str_replace('_', '', $registration->getExtensionName()) . '_' . $this->getType();
    }
}
