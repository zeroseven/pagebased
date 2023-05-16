<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Domain\Model\Demand\ObjectDemandInterface;
use Zeroseven\Rampage\Domain\Repository\ContactRepository;
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
        $lastCharacter = strtolower(substr($word, -1));
        $lastTwoCharacters = strtolower(substr($word, -2));

        if ($lastCharacter === 'y' && !in_array($lastTwoCharacters, ['ay', 'ey', 'iy', 'oy', 'uy'], true)) {
            return substr_replace($word, 'ies', -1);
        }

        if (in_array($lastCharacter, ['s', 'x', 'z'], true) || in_array($lastTwoCharacters, ['ch', 'sh'], true)) {
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
        $this->demand = $this->registration->getObject()->getDemandClass()->setParameterArray($this->settings);
    }

    public function applyRequestArguments(bool $respectContentParameter = null): void
    {
        if ($respectContentParameter) {
            $contentID = (int)($this->contentData['uid'] ?? 0);
            $requestID = (int)($this->requestArguments[ObjectDemandInterface::PARAMETER_CONTENT_ID] ?? 0);

            if ($contentID && $requestID && $contentID === $requestID) {
                $this->demand->setParameterArray($this->requestArguments);
            }
        } else {
            $this->demand->setParameterArray($this->requestArguments);
        }
    }

    public function listAction(): void
    {
        $this->applyRequestArguments(true);

        $repository = $this->registration->getObject()->getRepositoryClass();
        $objects = $repository->findByDemand($this->demand->setExcludeChildObjects(true));

        if (!$this->demand->getContentId() && $contentId = $this->contentData['uid'] ?? null) {
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
        $this->applyRequestArguments(false);

        // Pass variables to the fluid template
        $this->view->assignMultiple([
            'categories' => $this->registration->getCategory()->getRepositoryClass()->findAll(),
            'tags' => TagUtility::getTagsByRegistration($this->registration),
            'topics' => GeneralUtility::makeInstance(TopicRepository::class)->findByRegistration($this->registration),
            'contacts' => GeneralUtility::makeInstance(ContactRepository::class)->findByRegistration($this->registration),
            'demand' => $this->demand
        ]);
    }
}
