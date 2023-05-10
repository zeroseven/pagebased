<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Domain\Repository\TopicRepository;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;
use Zeroseven\Rampage\Utility\TagUtility;

abstract class AbstractPageObjectController extends AbstractController implements PageObjectControllerInterface
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

    protected function pluralizeWord(string $word): string
    {
        $length = strlen($word);

        // It's not working with the word "boy". LOL
        if (strtolower($word[$length - 1]) === 'y') {
            return substr_replace($word, 'ies', -1);
        }

        if (in_array(strtolower($word[$length - 1]), ['s', 'x', 'z'], true) || in_array(strtolower(substr($word, -2)), ['ch', 'sh'], true)) {
            return $word . 'es';
        }

        return $word . 's';
    }

    public function initializeRegistration(): void
    {
        $this->registration = RegistrationService::getRegistrationByController(get_class($this));
    }

    public function initializeDemand(): void
    {
        $this->demand = $this->registration->getObject()->getDemandClass()->setParameterArray(array_merge($this->settings, $this->requestArguments));
    }

    public function getDemand(): DemandInterface
    {
        return $this->demand;
    }

    public function listAction(): void
    {
        $repository = $this->registration->getObject()->getRepositoryClass();
        $objects = $repository->findByDemand($this->demand);

        if (($contentId = ($this->contentData['uid'] ?? null)) && !$this->demand->getContentId()) {
            $this->demand->setContentId($contentId);
        }

        // Pass variables to the fluid template
        $this->view->assignMultiple([
            'objects' => $objects,
            'demand' => $this->demand,
            $this->pluralizeWord(strtolower($this->registration->getObject()->getName())) => $objects // alias variable
        ]);
    }

    public function filterAction(): void
    {
        // Pass variables to the fluid template
        $this->view->assignMultiple([
            'topics' => GeneralUtility::makeInstance(TopicRepository::class)->findByRegistration($this->registration),
            'tags' => TagUtility::getTagsByRegistration($this->registration),
            'categories' => ($categoryRegistration = $this->registration->getCategory()) && $categoryRegistration->getRepositoryClassName() ? $categoryRegistration->getRepositoryClass()->findAll() : null,
            'demand' => $this->demand
        ]);
    }
}
