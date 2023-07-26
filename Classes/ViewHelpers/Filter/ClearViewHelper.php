<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\ViewHelpers\Filter;

final class ClearViewHelper extends AbstractFilterLinkViewHelper
{
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
