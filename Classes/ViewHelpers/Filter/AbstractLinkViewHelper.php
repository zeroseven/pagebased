<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\ViewHelpers\Filter;

use TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use Zeroseven\Pagebased\Domain\Model\Demand\DemandInterface;
use Zeroseven\Pagebased\Domain\Model\Demand\ObjectDemandInterface;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;
use Zeroseven\Pagebased\Utility\ObjectUtility;
use Zeroseven\Pagebased\Utility\RootLineUtility;

abstract class AbstractLinkViewHelper extends ActionViewHelper
{
    protected ?DemandInterface $demand = null;

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        // Register demand argument
        $this->registerArgument('demand', 'object', sprintf('The demand object (instance of %s)', DemandInterface::class));
        $this->registerArgument('findList', 'bool', 'Find the next list in rootline');
    }

    /** @throws Exception */
    public function validateArguments(): void
    {
        parent::validateArguments();

        $this->initializeDemand();

        if (!$this->demand) {
            throw new Exception('Demand is undefined. Add argument "demand" to this viewHelper', 1678130615);
        }
    }

    protected function getRegistration(): ?Registration
    {
        return $this->templateVariableContainer->exists('registration') && ($registration = $this->templateVariableContainer->get('registration')) instanceof Registration
            ? $registration
            : ObjectUtility::isObject() ?? ObjectUtility::isCategory();
    }

    protected function initializeDemand(): void
    {
        if (($demand = $this->arguments['demand'] ?? ($this->templateVariableContainer->get('demand'))) instanceof DemandInterface) {
            $this->demand = $demand->getCopy();
        } elseif ($registration = $this->getRegistration()) {
            $this->demand = $registration->getObject()->getDemandClass();
        }
    }

    protected function overridePageUid(): void
    {
        if ($this->arguments['findList'] ?? false) {
            $listPlugin = $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/pagebased']['cache']['listPlugin'] ?? null;

            if (empty($listPlugin) && ($registration = $this->getRegistration() ?? RegistrationService::getRegistrationByDemandClass(get_class($this->demand)))) {
                $listPlugin = $GLOBALS['TYPO3_CONF_VARS']['USER']['zeroseven/pagebased']['cache']['listPlugin'] = RootLineUtility::findListPlugin($registration, null, true);
            }

            if (($uid = (int)($listPlugin['uid'] ?? 0)) && $pid = (int)($listPlugin['pid'] ?? 0)) {
                $this->arguments['arguments'][ObjectDemandInterface::PROPERTY_CONTENT_ID] = $uid;
                $this->arguments['pageUid'] = $pid;
                $this->arguments['section'] ?? $this->arguments['section'] = 'c' . $uid;
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

        // Set plugin name
        if (empty($this->arguments['pluginName'])) {
            $this->arguments['pluginName'] = 'List';
        }

        return parent::render();
    }
}
