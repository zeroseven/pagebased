<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use Zeroseven\Rampage\Domain\Model\Demand\DemandInterface;
use Zeroseven\Rampage\Domain\Model\Demand\ObjectDemandInterface;
use Zeroseven\Rampage\Domain\Repository\ContactRepository;
use Zeroseven\Rampage\Domain\Repository\TopicRepository;
use Zeroseven\Rampage\Event\AssignTemplateVariablesEvent;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;
use Zeroseven\Rampage\Utility\TagUtility;

abstract class AbstractObjectController extends AbstractController implements ObjectControllerInterface
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

    protected function getPluginSettings(int $uid): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        try {
            $flexForm = $queryBuilder
                ->select('pi_flexform')
                ->from('tt_content')
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
                ->execute()
                ->fetchOne();
        } catch (DBALException | Exception $e) {
            return null;
        }

        if ($flexForm && $pluginSettings = GeneralUtility::makeInstance(FlexFormService::class)->convertFlexFormContentToArray($flexForm)) {
            return $pluginSettings['settings'] ?? null;
        }

        return null;
    }

    public function initializeRegistration(): void
    {
        $this->registration = RegistrationService::getRegistrationByController(get_class($this));
    }

    public function initializeDemand(): void
    {
        $this->demand = $this->registration->getObject()->getDemandClass()->setParameterArray($this->settings);
    }

    public function listAction(): void
    {
        // Apply request arguments
        if (empty($requestID = (int)($this->requestArguments[ObjectDemandInterface::PROPERTY_CONTENT_ID] ?? 0)) || (int)($this->contentData['uid'] ?? 0) === $requestID) {
            $this->demand->setParameterArray($this->requestArguments);
        }

        $repository = $this->registration->getObject()->getRepositoryClass();
        $objects = $repository->findByDemand($this->demand->setExcludeChildObjects(true));

        if (!$this->demand->getContentId() && $contentId = $this->contentData['uid'] ?? null) {
            $this->demand->setContentId($contentId);
        }

        // Pass variables to the fluid template
        $this->view->assignMultiple(GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new AssignTemplateVariablesEvent([
            'objects' => $objects,
            'demand' => $this->demand,
            'registration' => $this->registration,
            $this->pluralizeWord(strtolower($this->registration->getObject()->getName())) => $objects // alias variable
        ], $this->registration, 'list'))->getVariables());
    }

    public function filterAction(): void
    {
        // Apply filter settings of the linked list plugin
        if (($listID = (int)($this->settings[ObjectDemandInterface::PROPERTY_CONTENT_ID] ?? 0)) && $settings = $this->getPluginSettings($listID)) {
            $this->demand->setParameterArray($settings, true);
            $this->view->getRenderingContext()->getVariableProvider()->add('settings', array_merge($settings, $this->settings));
        }

        // Apply request arguments
        $this->demand->setParameterArray($this->requestArguments);

        // Pass variables to the fluid template
        $this->view->assignMultiple(GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new AssignTemplateVariablesEvent([
            'categories' => $this->registration->getCategory()->getRepositoryClass()->findAll(),
            'tags' => TagUtility::getTagsByRegistration($this->registration),
            'topics' => GeneralUtility::makeInstance(TopicRepository::class)->findByRegistration($this->registration),
            'contacts' => GeneralUtility::makeInstance(ContactRepository::class)->findByRegistration($this->registration),
            'demand' => $this->demand,
            'registration' => $this->registration
        ], $this->registration, 'filter'))->getVariables());
    }
}
