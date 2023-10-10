<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\ViewHelpers\Filter;

use ReflectionClass;
use ReflectionException;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use Zeroseven\Pagebased\Domain\Model\Demand\AbstractObjectDemand;
use Zeroseven\Pagebased\Domain\Model\Demand\ObjectDemandInterface;
use Zeroseven\Pagebased\Exception\TypeException;
use Zeroseven\Pagebased\Exception\ValueException;

final class LinkViewHelper extends AbstractFilterLinkViewHelper
{
    protected const FILTER_ACTIVE_ATTRIBUTE = 'data-filter-active';

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('properties', 'array', 'Update demand properties');
        $this->registerArgument('toggle', 'bool', 'If selected, deselect value', false, true);
        $this->registerArgument('dataAttributes', 'bool', 'Set data attributes, if the filter is enabled', false, true);
    }

    /** @throws Exception | ReflectionException */
    public function validateArguments(): void
    {
        parent::validateArguments();

        if ($this->demand) {
            foreach (array_keys($this->arguments['properties'] ?? []) as $key) {
                if (!$this->demand->hasProperty($key)) {
                    throw new Exception(sprintf('Undefined property "%s" in demand class "%s". Allowed properties are %s', $key, (new ReflectionClass($this->demand))->getName(), implode(', ', array_map(static fn($property) => $property->getName(), $this->demand->getProperties()))), 1678130803);
                }
            }
        }
    }

    protected function overrideDemandProperties(): void
    {
        if ($arguments = $this->arguments['arguments'] ?? null) {
            $this->demand->setParameterArray($arguments, true);
        }

        if ($properties = $this->arguments['properties'] ?? null) {
            $this->demand->setProperties($properties, false, (bool)$this->arguments['toggle']);
        }
    }

    protected function overrideArguments(): void
    {
        $overrides = $this->demand->getParameterDiff($this->templateVariableContainer->get('settings'), [AbstractObjectDemand::PROPERTY_CONTENT_ID]);
        $this->arguments['arguments'] = array_merge((array)$this->arguments['arguments'], $overrides);
    }

    /** @throws TypeException | ValueException */
    protected function setDataAttributes(): void
    {
        if ($this->arguments['dataAttributes'] ?? null) {
            $matches = 0;

            foreach ($this->arguments['properties'] ?? [] as $key => $value) {
                if ($this->demand->hasProperty($key) && $this->demand->getProperty($key)->isActive($value)) {
                    ++$matches;
                }
            }

            // Set data attributes
            if ($matches > 0) {
                $this->tag->addAttribute(self::FILTER_ACTIVE_ATTRIBUTE, 'true');
                $this->tag->addAttribute('selected', 'selected');
            }
        }
    }

    /** @throws TypeException | ValueException */
    public function render(): string
    {
        $this->setDataAttributes();

        if (empty($this->arguments['section']) && $listUid = $this->demand->getProperty(ObjectDemandInterface::PROPERTY_CONTENT_ID)->getValue()) {
            $this->arguments['section'] = 'c' . $listUid;
        }
        if ($this->arguments['optionTag']) {
            $this->arguments['optionTag'] = TRUE;
        }

        return parent::render();
    }
}
