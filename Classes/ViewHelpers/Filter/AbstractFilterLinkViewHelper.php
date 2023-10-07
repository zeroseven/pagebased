<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\ViewHelpers\Filter;

use Zeroseven\Pagebased\Domain\Model\Demand\ObjectDemandInterface;
use Zeroseven\Pagebased\Utility\RootLineUtility;
use Zeroseven\Pagebased\ViewHelpers\AbstractLinkViewHelper;

abstract class AbstractFilterLinkViewHelper extends AbstractLinkViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('findList', 'bool', 'Find the next list in rootline');
    }

    protected function overridePageUid(): void
    {
        if ($this->arguments['findList'] ?? false) {
            $listPlugin = $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/pagebased']['cache']['listPlugin'] ?? null;

            if (empty($listPlugin)) {
                $listPlugin = $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/pagebased']['cache']['listPlugin'] = RootLineUtility::findListPlugin($this->registration, null, true);
            }

            if (($uid = (int)($listPlugin['uid'] ?? 0)) && $pid = (int)($listPlugin['pid'] ?? 0)) {
                $this->arguments['arguments'][ObjectDemandInterface::PROPERTY_CONTENT_ID] = $uid;
                $this->arguments['pageUid'] = $pid;

                if (empty($this->arguments['section'])) {
                    $this->arguments['section'] = 'c' . $uid;
                }
            }
        }
    }

    abstract protected function overrideDemandProperties(): void;

    abstract protected function overrideArguments(): void;

    public function render(): string
    {
        $this->overrideDemandProperties();
        $this->overrideArguments();
        $this->overridePageUid();

        return parent::render();
    }
}
