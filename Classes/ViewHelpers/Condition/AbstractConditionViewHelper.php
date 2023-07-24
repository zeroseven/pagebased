<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\ViewHelpers\Condition;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use Zeroseven\Pagebased\Exception\ValueException;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;

abstract class AbstractConditionViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('registration', 'string', 'The registration identifier', true);
        $this->registerArgument('negate', 'boolean', 'Negate the condition');
    }

    abstract protected function detectRegistration(): ?Registration;

    /** @throws Exception | ValueException */
    public function render(): string
    {
        $registration = RegistrationService::getRegistrationByIdentifier($this->arguments['registration'] ?? '');
        $match = $this->detectRegistration() && $this->detectRegistration()->getIdentifier() === $registration->getIdentifier();
        $negate = $this->arguments['negate'] ?? false;

        if ($match && !$negate || !$match && $negate) {
            return $this->renderChildren() ?: '1';
        }

        return '';
    }
}
