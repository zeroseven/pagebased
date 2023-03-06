<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\ViewHelpers\Filter;

use ReflectionClass;
use ReflectionException;
use TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use Zeroseven\Rampage\Domain\Model\Demand\AbstractDemand;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;

class LinkViewHelper extends ActionViewHelper
{
    protected ?DemandInterface $demand;

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        // Register demand argument
        $this->registerArgument('demand', 'object', sprintf('The demand object (instance of %s)', DemandInterface::class));
        $this->registerArgument('properties', 'array', 'Update demand properties');
    }

    /** @throws Exception | ReflectionException */
    public function validateArguments(): void
    {
        parent::validateArguments();

        $this->initializeDemand();

        if ($this->demand) {
            foreach (array_keys($this->arguments['properties'] ?? []) as $key) {
                if (!$this->demand->hasProperty($key)) {
                    throw new Exception(sprintf('Undefined property "%s" in demand class "%s"', $key, (new ReflectionClass($this->demand))->getName()), 1678130803);
                }
            }
        } else {
            throw new Exception('Demand is undefined. Add argument "demand" to this viewHelper', 1678130615);
        }
    }

    protected function overrideDemandProperties(): void
    {
        $this->demand->setProperties(true, $this->arguments['properties'] ?? [], $this->arguments['arguments'] ?? []);
    }

    protected function initializeDemand(): void
    {
        $this->demand = ($value = $this->arguments['demand'] ?? ($this->templateVariableContainer->get('demand'))) instanceof DemandInterface
            ? $value
            : null;
    }

    protected function overrideArguments(): void
    {
        $overrides = $this->demand->getParameterDiff($this->templateVariableContainer->get('settings'), [AbstractDemand::PARAMETER_CONTENT_ID]);
        $this->arguments['arguments'] = array_merge((array)$this->arguments['arguments'], $overrides);
    }

    public function render(): string
    {
        $this->overrideDemandProperties();
        $this->overrideArguments();

        if (empty($this->arguments['pluginName'])) {
            $this->arguments['pluginName'] = 'List';
        }

        return parent::render();
    }

}
