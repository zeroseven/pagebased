<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\ViewHelpers\Filter;

use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;

final class ClearViewHelper extends AbstractLinkViewHelper
{
    protected ?DemandInterface $demand;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
    }

    protected function overrideDemandProperties(): void
    {
        $this->demand->clear();
    }

    protected function overrideArguments(): void
    {
        if (is_array($arguments = $this->arguments['arguments'] ?? null)) {
            foreach (array_keys($arguments) as $key) {
                if ($this->demand->hasProperty($key)) {
                    $this->arguments['arguments'][$key] = (string)$this->demand->getProperty($key);
                }
            }
        }
    }
}
