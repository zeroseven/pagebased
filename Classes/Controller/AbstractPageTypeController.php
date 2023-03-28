<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Zeroseven\Rampage\Domain\Model\Demand\AbstractDemand;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Domain\Repository\TopicRepository;
use Zeroseven\Rampage\Exception\RegistrationException;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;

abstract class AbstractPageTypeController extends AbstractController implements PageTypeControllerInterface
{
    protected ?Registration $registration = null;
    protected ?DemandInterface $demand = null;
    protected array $requestArguments = [];

    public function initializeAction(): void
    {
        parent::initializeAction();

        if ($extbaseSetup = $this->request->getAttribute('extbase')) {
            $requestKey = strtolower('tx_' . $extbaseSetup->getControllerExtensionName() . '_list');

            $listArguments = GeneralUtility::_GP($requestKey) ?: [];
        } else {
            $listArguments = [];
        }

        $this->requestArguments = array_merge($this->request->getArguments(), $listArguments);

        $this->initializeRegistration();
        $this->initializeDemand();
    }

    protected function resolveView(): ViewInterface
    {
        $view = parent::resolveView();
        $view->assign('requestArguments', $this->requestArguments);

        return $view;
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
        // Pass variables to the fluid template
        $this->view->assignMultiple([
            'topics' => GeneralUtility::makeInstance(TopicRepository::class)->findByRegistration($this->registration),
            'demand' => $this->demand
        ]);
    }
}
