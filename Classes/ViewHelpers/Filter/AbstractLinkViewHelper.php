<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\ViewHelpers\Filter;

use ReflectionClass;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use Zeroseven\Pagebased\Domain\Model\Demand\DemandInterface;
use Zeroseven\Pagebased\Domain\Model\Demand\ObjectDemandInterface;
use Zeroseven\Pagebased\Exception\ValueException;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;
use Zeroseven\Pagebased\Utility\ObjectUtility;
use Zeroseven\Pagebased\Utility\RootLineUtility;

abstract class AbstractLinkViewHelper extends ActionViewHelper
{
    protected ?DemandInterface $demand = null;
    protected ?Registration $registration = null;

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('demand', 'object', sprintf('The demand object (instance of %s)', DemandInterface::class));
        $this->registerArgument('registration', 'string', 'The registration identifier');
        $this->registerArgument('findList', 'bool', 'Find the next list in rootline');
    }

    public function validateArguments(): void
    {
        parent::validateArguments();
        $this->initializeRegistration();
    }

    /** @throws ValueException | Exception */
    protected function initializeRegistration(): void
    {
        // Try to get demand
        if (($demand = $this->arguments['demand'] ?? $this->templateVariableContainer->get('demand')) instanceof DemandInterface) {
            $this->demand = $demand->getCopy();
        }

        // Try to get registration
        if (($registrationIdentifier = $this->arguments['registration'] ?? null) && $registration = RegistrationService::getRegistrationByIdentifier($registrationIdentifier)) {
            $this->registration = $registration;
        } elseif (($registration = $this->templateVariableContainer->get('registration')) instanceof Registration) {
            $this->registration = $registration;
        } else {
            $this->registration = ObjectUtility::isObject() ?? ObjectUtility::isCategory();
        }

        // Try to get registration by the demand class
        if ($this->registration === null && $this->demand) {
            $this->registration = RegistrationService::getRegistrationByDemand($this->demand);
        }

        // Try to get demand from registration
        if ($this->registration && $this->demand === null) {
            $this->demand = $this->registration->getObject()->getDemandClass();
        }

        // Unfortunately didn't work :(
        if ($this->registration === null && $this->demand === null) {
            throw new Exception(sprintf('The registration object and demand object could not be determined. Add arguments "registration" or "demand" to the ViewHelper ("%s").', get_class($this)), 1690362083);
        }
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
