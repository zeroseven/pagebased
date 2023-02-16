<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use LogicException;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use Zeroseven\Rampage\Controller\AbstractPageTypeController;
use Zeroseven\Rampage\Controller\PageTypeControllerInterface;
use Zeroseven\Rampage\Domain\Model\AbstractPageCategory;
use Zeroseven\Rampage\Domain\Model\AbstractPageType;
use Zeroseven\Rampage\Domain\Model\Demand\AbstractDemand;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Domain\Model\PageTypeInterface;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\PageObjectRegistration;
use Zeroseven\Rampage\Registration\RegistrationService;

class ValidateRegistrationEvent
{
    /** @throws RegistrationException */
    protected function checkPageObjectRegistration(PageObjectRegistration $pageObjectRegistration): void
    {
        $objectClassName = $pageObjectRegistration->getObjectClassName();

        // Check class inheritance of object model
        if (!is_subclass_of($objectClassName, PageTypeInterface::class)) {
            throw new RegistrationException(sprintf('The class "%s" is not an instance of "%s". You can simply extend a class "%s" or "%s".', $objectClassName, PageTypeInterface::class, AbstractPageType::class, AbstractPageCategory::class), 1676063874);
        }

        // Check class inheritance of the controller
        if (($controllerClassName = $pageObjectRegistration->getControllerClassName()) && !is_subclass_of($controllerClassName, PageTypeControllerInterface::class)) {
            throw new RegistrationException(sprintf('The controller "%s" is not an instance of "%s". You can simply extend class "%s".', $controllerClassName, PageTypeControllerInterface::class, AbstractPageTypeController::class), 1676498615);
        }

        if (($demandClassName = $pageObjectRegistration->getDemandClassName()) && !is_subclass_of($demandClassName, DemandInterface::class)) {
            throw new RegistrationException(sprintf('The demand "%s" is not an instance of "%s". You can simply extend the class "%s".', $demandClassName, DemandInterface::class, AbstractDemand::class), 1676535114);
        }

        // Check the persistence configuration
        try {
            if (($tableName = GeneralUtility::makeInstance(DataMapper::class)->getDataMap($objectClassName)->getTableName()) !== 'pages') {
                throw new RegistrationException(sprintf('The object must be stored in table "pages" instead of "%s". See https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Persistence.html#extbase-manual-mapping', $tableName), 1676066023);
            }
        } catch (Exception $e) {
            throw new RegistrationException(sprintf('The class "%s" does not exists. %s', $objectClassName, $e->getMessage()), 1676065930);
        } catch (LogicException $e) {
        }
    }

    /** @throws RegistrationException | Exception */
    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        foreach (RegistrationService::getRegistrations() as $registration) {
            $this->checkPageObjectRegistration($registration->getObject());

            if ($registration->getCategory()->isEnabled()) {
                $this->checkPageObjectRegistration($registration->getCategory());
            }
        }
    }
}
