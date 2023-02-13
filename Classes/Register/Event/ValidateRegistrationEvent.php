<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Register\Event;

use LogicException;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use Zeroseven\Rampage\Domain\Model\AbstractPageCategory;
use Zeroseven\Rampage\Domain\Model\AbstractPageType;
use Zeroseven\Rampage\Domain\Model\PageTypeInterface;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Exception\ValueException;
use Zeroseven\Rampage\Register\PageObjectRegistration;
use Zeroseven\Rampage\Register\RegisterService;

class ValidateRegistrationEvent
{
    protected function checkPageObjectRegistration(PageObjectRegistration $pageObjectRegistration): void
    {
        $className = $pageObjectRegistration->getObjectClassName();

        // Check class inheritance
        if (!is_subclass_of($className, PageTypeInterface::class)) {
            throw new ValueException(sprintf('The class "%s" is not an instance of "%s". You can simply extend a class "%s" or "%s".', $className, PageTypeInterface::class, AbstractPageType::class, AbstractPageCategory::class), 1676063874);
        }

        // Check the persistence configuration
        try {
            if (($tableName = GeneralUtility::makeInstance(DataMapper::class)->getDataMap($className)->getTableName()) !== 'pages') {
                throw new RegistrationException(sprintf('The object must be stored in table "pages" instead of "%s". See https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Persistence.html#extbase-manual-mapping', $tableName), 1676066023);
            }
        } catch (Exception $e) {
            throw new RegistrationException(sprintf('The class "%s" does not exists. %s', $className, $e->getMessage()), 1676065930);
        } catch (LogicException $e) {
        }
    }

    /** @throws ValueException | RegistrationException | Exception */
    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        foreach (RegisterService::getRegistrations() as $registration) {
            $this->checkPageObjectRegistration($registration->getObect());

            if ($registration->getCategory()->isEnabled()) {
                $this->checkPageObjectRegistration($registration->getCategory());
            }
        }
    }
}
