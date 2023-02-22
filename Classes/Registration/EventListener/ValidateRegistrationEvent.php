<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use LogicException;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Imaging\IconRegistry;
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
use Zeroseven\Rampage\Domain\Repository\AbstractPageRepository;
use Zeroseven\Rampage\Domain\Repository\RepositoryInterface;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\PageObjectRegistration;
use Zeroseven\Rampage\Registration\RegistrationService;

class ValidateRegistrationEvent
{
    /** @throws RegistrationException */
    protected function checkPageObjectRegistration(PageObjectRegistration $pageObjectRegistration): void
    {
        // Check class inheritance of the controller
        if (($controllerClassName = $pageObjectRegistration->getControllerClassName()) && !is_subclass_of($controllerClassName, PageTypeControllerInterface::class)) {
            throw new RegistrationException(sprintf('The controller "%s" is not an instance of "%s". You can simply extend class "%s".', $controllerClassName, PageTypeControllerInterface::class, AbstractPageTypeController::class), 1676498615);
        }

        // Check demand
        if (($demandClassName = $pageObjectRegistration->getDemandClassName()) && !is_subclass_of($demandClassName, DemandInterface::class)) {
            throw new RegistrationException(sprintf('The demand "%s" is not an instance of "%s". You can simply extend the class "%s".', $demandClassName, DemandInterface::class, AbstractDemand::class), 1676535114);
        }

        // Check repository
        if (($repositoryClassName = $pageObjectRegistration->getRepositoryClassName()) && !is_subclass_of($repositoryClassName, RepositoryInterface::class)) {
            throw new RegistrationException(sprintf('The repository "%s" is not an instance of "%s". You can simply extend the class "%s".', $repositoryClassName, RepositoryInterface::class, AbstractPageRepository::class), 1676667419);
        }

        // Check the persistence configuration
        if ($objectClassName = $pageObjectRegistration->getObjectClassName()) {
            try {
                if (($tableName = GeneralUtility::makeInstance(DataMapper::class)->getDataMap($objectClassName)->getTableName()) !== 'pages') {
                    throw new RegistrationException(sprintf('The object "%s" must be stored in table "pages" instead of "%s". See https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Persistence.html#extbase-manual-mapping', $pageObjectRegistration->getTitle(), $tableName), 1676066023);
                }
            } catch (Exception $e) {
                throw new RegistrationException(sprintf('The class "%s" does not exists. %s', $objectClassName, $e->getMessage()), 1676065930);
            } catch (LogicException $e) {
            }
        }
    }

    /** @throws RegistrationException */
    protected function checkPageTypeRegistration(PageObjectRegistration $pageTypeRegistration): void
    {
        $objectClassName = $pageTypeRegistration->getObjectClassName();

        // Check class inheritance of object model
        if (!is_subclass_of($objectClassName, PageTypeInterface::class)) {
            throw new RegistrationException(sprintf('The class "%s" is not an instance of "%s". You can simply extend a class "%s" or "%s".', $objectClassName, PageTypeInterface::class, AbstractPageType::class, AbstractPageCategory::class), 1676063874);
        }

        // Check page icons
        if (($iconRegistry = GeneralUtility::makeInstance(IconRegistry::class)) && $iconIdentifier = $pageTypeRegistration->getIconIdentifier()) {
            if (!$iconRegistry->isRegistered($iconIdentifier)) {
                throw new RegistrationException(sprintf('The icon "%s" for the page type "%s" is not registered. More information: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Icon/Index.html#registration', $iconIdentifier, $pageTypeRegistration->getTitle()), 1676552125);
            }

            $hideInMenuIconIdentifier = $pageTypeRegistration->getIconIdentifier(true);

            if (!$iconRegistry->isRegistered($hideInMenuIconIdentifier)) {
                throw new RegistrationException(sprintf('For icon "%s", icon "%s" must also be present for pages that are hidden in the navigation.', $iconIdentifier, $hideInMenuIconIdentifier), 1676553316);
            }
        }

        $this->checkPageObjectRegistration($pageTypeRegistration);
    }

    /** @throws RegistrationException | Exception */
    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        foreach (RegistrationService::getRegistrations() as $registration) {
            $this->checkPageTypeRegistration($registration->getObject());

            if ($registration->getCategory()->isEnabled()) {
                $this->checkPageTypeRegistration($registration->getCategory());
            }
        }
    }
}
