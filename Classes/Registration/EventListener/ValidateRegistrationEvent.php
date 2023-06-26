<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Registration\EventListener;

use LogicException;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use Zeroseven\Rampage\Controller\AbstractObjectController;
use Zeroseven\Rampage\Controller\ObjectControllerInterface;
use Zeroseven\Rampage\Domain\Model\AbstractCategory;
use Zeroseven\Rampage\Domain\Model\AbstractObject;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Domain\Model\CategoryInterface;
use Zeroseven\Rampage\Domain\Model\Demand\AbstractDemand;
use Zeroseven\Rampage\Domain\Model\Demand\AbstractObjectDemand;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Domain\Model\Demand\GenericDemand;
use Zeroseven\Rampage\Domain\Model\Demand\GenericObjectDemand;
use Zeroseven\Rampage\Domain\Model\Demand\ObjectDemandInterface;
use Zeroseven\Rampage\Domain\Model\ObjectInterface;
use Zeroseven\Rampage\Domain\Repository\AbstractCategoryRepository;
use Zeroseven\Rampage\Domain\Repository\AbstractObjectRepository;
use Zeroseven\Rampage\Domain\Repository\CategoryRepositoryInterface;
use Zeroseven\Rampage\Domain\Repository\ObjectRepositoryInterface;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\AbstractRegistrationEntityProperty;
use Zeroseven\Rampage\Registration\AbstractRegistrationPluginProperty;
use Zeroseven\Rampage\Registration\CategoryRegistration;
use Zeroseven\Rampage\Registration\ObjectRegistration;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;

class ValidateRegistrationEvent
{

    /** @throws RegistrationException */
    protected function checkPageEntityConfiguration(AbstractRegistrationEntityProperty $entity): void
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
        if ($objectRegistration->getClassName()) {
            if (!is_subclass_of($objectRegistration->getClassName(), ObjectInterface::class)) {
                throw new RegistrationException(sprintf('For registration of "%s" a domain model of type "%s" is required. You can simply extend a class "%s".', $objectRegistration->getTitle(), ObjectInterface::class, AbstractObject::class), 1684310714);
            }
        } else {
            throw new RegistrationException(sprintf('The registration requires a domain model of type "%s". Use "ObjectRegistration::setClassName()".', ObjectInterface::class), 1684310718);
        }

        // Check class inheritance of the controller
        if ($className = $objectRegistration->getControllerClassName()) {
            if (!is_subclass_of($className, ObjectControllerInterface::class)) {
                throw new RegistrationException(sprintf('The class "%s" must be an instance of "%s". Yau can simply extend the class "%s"', $className, ObjectControllerInterface::class, AbstractObjectController::class), 1680722536);
            }
        } else {
            throw new RegistrationException(sprintf('An extbase controller for class "%s" ("%s") is required. Use "ObjectRegistration::setControllerClass()" to define the controller.', $objectRegistration->getClassName(), $objectRegistration->getTitle()), 1680722535);
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
            throw new RegistrationException(sprintf('Please provide a repository of "%s" for the object "%s". Use "ObjectRegistration::setRepositoryClass()" to define a repository.', ObjectRepositoryInterface::class, $objectRegistration->getTitle()), 1680722762);
        }
    }

    /** @throws RegistrationException | Exception */
    protected function checkCategoryConfiguration(CategoryRegistration $categoryRegistration): void
    {
        // Check domain model
        if ($categoryRegistration->getClassName()) {
            if (!is_subclass_of($categoryRegistration->getClassName(), CategoryInterface::class)) {
                throw new RegistrationException(sprintf('The class "%s" is not an instance of "%s". You can simply extend a class "%s".', $categoryRegistration->getClassName(), CategoryInterface::class, AbstractCategory::class), 1676063874);
            }
        } else {
            throw new RegistrationException(sprintf('The registration requires a category domain model of type "%s". Use "CategoryRegistration::setClassName()" to define a model. You can extend the class "%s".', CategoryInterface::class, AbstractCategory::class), 1678708348);
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

        // Check the persistence configuration and document type
        $documentType = $categoryRegistration->getDocumentType();
        $recordType = (int)GeneralUtility::makeInstance(DataMapper::class)->getDataMap($categoryRegistration->getClassName())->getRecordType();

        if (empty($documentType)) {
            throw new RegistrationException(sprintf('The object "%s" requires a documentType.', $categoryRegistration->getClassName()), 1687555268);
        }

        if (empty($recordType)) {
            throw new RegistrationException(sprintf('The object "%s" requires a "recordType" configuration. See https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Persistence.html#extbase-manual-mapping', $categoryRegistration->getClassName()), 1680721463);
        }

        if ($documentType !== $recordType) {
            throw new RegistrationException(sprintf('The configured recordType of the "%s" extbase configuration is not equal to the registration settings', $categoryRegistration->getTitle()), 1687555363);
        }

        $documentTypes = array_map(static fn(Registration $registration) => $registration->getCategory()->getDocumentType(), RegistrationService::getRegistrations());
        $duplicates = array_unique(array_diff_assoc($documentTypes, array_unique($documentTypes)));

        foreach ($duplicates as $duplicate) {
            if ($duplicate === $documentType) {
                throw new RegistrationException(sprintf('The documentType "%d" is already registered. Please check the documentType on category "%s".', $documentType, $categoryRegistration->getClassName()), 1687556094);
            }
        }
    }

    /** @throws RegistrationException */
    protected function checkPluginConfiguration(AbstractRegistrationPluginProperty $pluginRegistration): void
    {
        // Check plugin icon
        if (($iconIdentifier = $pluginRegistration->getIconIdentifier()) && !GeneralUtility::makeInstance(IconRegistry::class)->isRegistered($iconIdentifier)) {
            throw new RegistrationException(sprintf('The icon "%s" for the plugin "%s" is not registered. More information: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Icon/Index.html#registration', $iconIdentifier, $pluginRegistration->getTitle()), 1680723529);
        }
    }

    /** @throws RegistrationException | Exception */
    protected function checkRegistration(Registration $registration): void
    {
        if ($objectRegistration = $registration->getObject()) {
            $this->checkPageObjectRegistration($objectRegistration);
            $this->checkPageEntityConfiguration($objectRegistration);
        }

        if ($categoryRegistration = $registration->getCategory()) {
            $this->checkCategoryConfiguration($categoryRegistration);
            $this->checkPageEntityConfiguration($categoryRegistration);
        }
    }

    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        if (Environment::isCli()) {
            foreach (RegistrationService::getRegistrations() as $registration) {
                try {
                    $this->checkRegistration($registration);
                } catch (RegistrationException $e) {
                    DebugUtility::debug($e->getMessage(), 'Error: ' . $e->getCode());
                }
            }
        }
    }
}
