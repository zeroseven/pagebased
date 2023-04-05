<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use LogicException;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use Zeroseven\Rampage\Controller\AbstractPageObjectController;
use Zeroseven\Rampage\Controller\PageObjectControllerInterface;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Domain\Model\AbstractPageCategory;
use Zeroseven\Rampage\Domain\Model\AbstractPageObject;
use Zeroseven\Rampage\Domain\Model\Demand\AbstractDemand;
use Zeroseven\Rampage\Domain\Model\Demand\AbstractObjectDemand;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Domain\Model\Demand\GenericDemand;
use Zeroseven\Rampage\Domain\Model\Demand\GenericObjectDemand;
use Zeroseven\Rampage\Domain\Model\Demand\ObjectDemandInterface;
use Zeroseven\Rampage\Domain\Model\PageObjectInterface;
use Zeroseven\Rampage\Domain\Model\PageTypeInterface;
use Zeroseven\Rampage\Domain\Repository\AbstractCategoryRepository;
use Zeroseven\Rampage\Domain\Repository\AbstractObjectRepository;
use Zeroseven\Rampage\Domain\Repository\CategoryRepositoryInterface;
use Zeroseven\Rampage\Domain\Repository\ObjectRepositoryInterface;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\AbstractEntityRegistration;
use Zeroseven\Rampage\Registration\AbstractPluginRegistration;
use Zeroseven\Rampage\Registration\CategoryRegistration;
use Zeroseven\Rampage\Registration\ObjectRegistration;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;

class ValidateRegistrationEvent
{

    /** @throws RegistrationException */
    protected function checkPageEntityConfiguration(AbstractEntityRegistration $entity): void
    {
        // Check the persistence configuration
        if ($className = $entity->getClassName()) {
            try {
                if (($tableName = GeneralUtility::makeInstance(DataMapper::class)->getDataMap($className)->getTableName()) !== AbstractPage::TABLE_NAME) {
                    throw new RegistrationException(sprintf('The object "%s" must be stored in table "pages" instead of "%s". See https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Persistence.html#extbase-manual-mapping', $entity->getTitle(), $tableName), 1676066023);
                }
            } catch (Exception | LogicException $e) {
                throw new RegistrationException(sprintf('Class mapping for page entity "%s" failed (ERROR: %s). See https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Persistence.html#extbase-manual-mapping', $entity->getClassName(), $e->getMessage()), 1680720144);
            }
        }
    }

    /** @throws RegistrationException */
    protected function checkPageObjectRegistration(ObjectRegistration $objectRegistration): void
    {
        // Check domain model
        if (empty($className = $objectRegistration->getClassName()) || !is_subclass_of($className, PageObjectInterface::class)) {
            throw new RegistrationException(sprintf('For registration of "%s" a domain model of type "%s" is required. You can simply extend a class "%s".', $objectRegistration->getTitle(), PageObjectInterface::class, AbstractPageObject::class), 1680721601);
        }

        // Check class inheritance of the controller
        if ($controllerClassName = $objectRegistration->getControllerClassName()) {
            if (!is_subclass_of($controllerClassName, PageObjectControllerInterface::class)) {
                throw new RegistrationException(sprintf('The class "%s" must be an instance of "%s". Yau can simply extend the class "%s"', $className, PageObjectControllerInterface::class, AbstractPageObjectController::class), 1680722536);
            }
        } else {
            throw new RegistrationException(sprintf('An extbase controller for class "%s" ("%s") is required.', $objectRegistration->getClassName(), $objectRegistration->getTitle()), 1680722535);
        }

        // Check demand
        if (!($objectRegistration->getDemandClass() instanceof ObjectDemandInterface)) {
            throw new RegistrationException(sprintf('The demand of object "%s" is not an instance of "%s". You can simply extend the class "%s" or build an instance by the "%s".', $objectRegistration->getClassName(), ObjectDemandInterface::class, AbstractObjectDemand::class, GenericObjectDemand::class), 1680722737);
        }

        // Check repository
        if ($className = $objectRegistration->getRepositoryClassName()) {
            if (!is_subclass_of($className, ObjectRepositoryInterface::class)) {
                throw new RegistrationException(sprintf('The repository "%s" is not a subclass of "%s". You can simply extend the class "%s".', $className, ObjectRepositoryInterface::class, AbstractObjectRepository::class), 1680722761);
            }
        } else {
            throw new RegistrationException(sprintf('Please provide a repository of "%s" for the object "%s"', ObjectRepositoryInterface::class, $objectRegistration->getTitle()), 1680722762);
        }
    }

    /** @throws RegistrationException */
    protected function checkCategoryConfiguration(CategoryRegistration $categoryRegistration): void
    {
        // Check domain model
        if ($categoryRegistration->getClassName()) {
            if (!is_subclass_of($categoryRegistration->getClassName(), PageTypeInterface::class)) {
                throw new RegistrationException(sprintf('The class "%s" is not an instance of "%s". You can simply extend a class "%s".', $categoryRegistration->getClassName(), PageTypeInterface::class, AbstractPageCategory::class), 1676063874);
            }
        } else {
            throw new RegistrationException(sprintf('The registration requires a domain model of type "%s". You can extend the class "%s".', PageTypeInterface::class, AbstractPageCategory::class), 1678708348);
        }

        // Check demand class
        if (!($categoryRegistration->getDemandClass() instanceof DemandInterface)) {
            throw new RegistrationException(sprintf('The demand of object "%s" is not an instance of "%s". You can simply extend the class "%s" or build an instance by the "%s".', $categoryRegistration->getClassName(), ObjectDemandInterface::class, AbstractDemand::class, GenericDemand::class), 1680720699);
        }

        // Check repository
        if ($className = $categoryRegistration->getRepositoryClassName()) {
            if (!is_subclass_of($className, CategoryRepositoryInterface::class)) {
                throw new RegistrationException(sprintf('The repository "%s" is not a subclass of "%s". You can simply extend the class "%s".', $className, CategoryRepositoryInterface::class, AbstractCategoryRepository::class), 1680721292);
            }
        } else {
            throw new RegistrationException(sprintf('Please provide a repository of "%s" for category "%s"', CategoryRepositoryInterface::class, $categoryRegistration->getTitle()), 1678708348);
        }

        // Check page icons
        if (($iconRegistry = GeneralUtility::makeInstance(IconRegistry::class)) && $iconIdentifier = $categoryRegistration->getIconIdentifier()) {
            if (!$iconRegistry->isRegistered($iconIdentifier)) {
                throw new RegistrationException(sprintf('The icon "%s" for the page type "%s" is not registered. More information: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Icon/Index.html#registration', $iconIdentifier, $categoryRegistration->getTitle()), 1676552125);
            }

            $hideInMenuIconIdentifier = $categoryRegistration->getIconIdentifier(true);

            if (!$iconRegistry->isRegistered($hideInMenuIconIdentifier)) {
                throw new RegistrationException(sprintf('For icon "%s", icon "%s" must also be present for pages that are hidden in the navigation.', $iconIdentifier, $hideInMenuIconIdentifier), 1676553316);
            }
        }

        // Check the persistence configuration
        try {
            if (!GeneralUtility::makeInstance(DataMapper::class)->getDataMap($categoryRegistration->getClassName())->getRecordType()) {
                throw new RegistrationException(sprintf('The object "%s" requires a "recordType" configuration. See https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Persistence.html#extbase-manual-mapping', $categoryRegistration->getTitle()), 1680721463);
            }
        } catch (Exception | LogicException $e) {
        }
    }

    /** @throws RegistrationException */
    protected function checkPluginConfiguration(AbstractPluginRegistration $pluginRegistration): void
    {
        // Check plugin icon
        if (($iconIdentifier = $pluginRegistration->getIconIdentifier()) && !GeneralUtility::makeInstance(IconRegistry::class)->isRegistered($iconIdentifier)) {
            throw new RegistrationException(sprintf('The icon "%s" for the plugin "%s" is not registered. More information: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Icon/Index.html#registration', $iconIdentifier, $pluginRegistration->getTitle()), 1680723529);
        }
    }

    /** @throws RegistrationException */
    protected function checkRegistration(Registration $registration): void
    {
        if ($registration->hasObject() && $objectRegistration = $registration->getObject()) {
            $this->checkPageEntityConfiguration($objectRegistration);
            $this->checkPageObjectRegistration($objectRegistration);
        } else {
            throw new RegistrationException(sprintf('An object must be configured in extension "%s". Please call "setObject()" methode, contains instance of "%s"', $registration->getExtensionName(), ObjectRegistration::class), 1678708145);
        }

        if ($registration->hasCategory() && $categoryRegistration = $registration->getCategory()) {
            $this->checkPageEntityConfiguration($categoryRegistration);
            $this->checkCategoryConfiguration($categoryRegistration);
        } else {
            throw new RegistrationException(sprintf('An category must be configured in extension "%s". Please call "setCategory()" methode, contains instance of "%s"', $registration->getExtensionName(), CategoryRegistration::class), 1680694223);
        }

        if ($registration->hasListPlugin() && $listPlugin = $registration->getListPlugin()) {
        }
    }

    /** @throws RegistrationException */
    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        foreach (RegistrationService::getRegistrations() as $registration) {
            $this->checkRegistration($registration);
        }
    }
}
