<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\ViewHelpers\Filter;

use ReflectionClass;
use ReflectionException;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use Zeroseven\Rampage\Domain\Model\Demand\AbstractDemand;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;

class LinkViewHelper extends AbstractLinkViewHelper
{
    protected ?DemandInterface $demand;

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('properties', 'array', 'Update demand properties');
    }

    /** @throws Exception | ReflectionException */
    public function validateArguments(): void
    {
        parent::validateArguments();

        if ($this->demand) {
            foreach (array_keys($this->arguments['properties'] ?? []) as $key) {
                if (!$this->demand->hasProperty($key)) {
                    throw new Exception(sprintf('Undefined property "%s" in demand class "%s"', $key, (new ReflectionClass($this->demand))->getName()), 1678130803);
                }
            }
        }
    }

    protected function overrideDemandProperties(): void
    {
        $this->demand->setProperties(true, $this->arguments['properties'] ?? [], $this->arguments['arguments'] ?? []);
    }

    protected function overrideArguments(): void
    {
        $overrides = $this->demand->getParameterDiff($this->templateVariableContainer->get('settings'), [AbstractDemand::PARAMETER_CONTENT_ID]);
        $this->arguments['arguments'] = array_merge((array)$this->arguments['arguments'], $overrides);
    }
}
