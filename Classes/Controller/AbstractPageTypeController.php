<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Zeroseven\Rampage\Domain\Model\Demand\AbstractDemand;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;

abstract class AbstractPageTypeController extends AbstractController implements PageTypeControllerInterface
{
    protected ?Registration $registration = null;
    protected ?DemandInterface $demand = null;

    public function initializeAction(): void
    {
        parent::initializeAction();

        $this->initializeRegistration();
        $this->initializeDemand();
    }

    public function initializeRegistration(): void
    {
        $this->registration = RegistrationService::getRegistrationByController(get_class($this));
    }

    public function initializeDemand(): void
    {
        $this->demand = AbstractDemand::makeInstance($this->registration->getObject(), array_merge($this->settings, $this->requestArguments));
    }

    public function getDemand(): DemandInterface
    {
        return $this->demand;
    }

    public function listAction(): void
    {
        $repository = GeneralUtility::makeInstance(ObjectManager::class)->get($this->registration->getObject()->getRepositoryClassName());
        $objects = $repository->findByDemand($this->demand);

        if (($contentID = ($this->contentData['uid'] ?? null)) && !$this->demand->getContentId()) {
            $this->demand->setContentId($contentID);
        }

        // Pass variables to the fluid template
        $this->view->assignMultiple([
            'objects' => $objects,
            'demand' => $this->demand
        ]);
    }

    public function filterAction(): void
    {
    }
}
