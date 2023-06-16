<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\ViewHelpers\Condition;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use Zeroseven\Rampage\Exception\ValueException;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;

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
