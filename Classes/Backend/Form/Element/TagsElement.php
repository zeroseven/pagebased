<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Backend\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Rampage\Exception\ValueException;
use Zeroseven\Rampage\Registration\Registration;
use Zeroseven\Rampage\Registration\RegistrationService;
use Zeroseven\Rampage\Utility\DetectionUtility;
use Zeroseven\Rampage\Utility\TagUtility;

class TagsElement extends AbstractFormElement
{
    protected string $name;
    protected string $id;
    protected string $value;
    protected string $placeholder;
    protected ?Registration $registration;
    protected int $languageUid;

    /** @throws ValueException */
    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);

        $parameterArray = $this->data['parameterArray'] ?? [];
        $placeholder = $parameterArray['fieldConf']['config']['placeholder'] ?? '';
        $sysLanguageUid = $this->data['databaseRow']['sys_language_uid'] ?? 0;

        $this->name = $parameterArray['itemFormElName'] ?? '';
        $this->id = $parameterArray['itemFormElID'] ?? '';
        $this->value = $parameterArray['itemFormElValue'] ?? '';
        $this->placeholder = str_starts_with($placeholder, 'LLL') ? $this->getLanguageService()->sL($placeholder) : $placeholder;
        $this->languageUid = (int)($sysLanguageUid[0] ?? $sysLanguageUid);
        $this->registration = ($registrationIdentifier = $parameterArray['fieldConf']['config']['registrationIdentifier'] ?? null)
            ? RegistrationService::getRegistrationByIdentifier($registrationIdentifier)
            : RegistrationService::getRegistrationByIdentifier($this->data['databaseRow'][DetectionUtility::REGISTRATION_FIELD_NAME] ?? '');
    }

    protected function getJavaScriptModule(): JavaScriptModuleInstruction
    {
        $tags = ($this->registration === null) ? [] : TagUtility::getTagsByRegistration($this->registration, true, $this->languageUid);

        return JavaScriptModuleInstruction::create(
            '@zeroseven/rampage/backend/TagsElement.js'
        )->instance($this->id, ...$tags);
    }

    protected function getStylesheetFile(): string
    {
        return 'EXT:rampage/Resources/Public/Css/Backend/Tagin.css';
    }

    protected function renderHtml(): string
    {
        $fieldWizardResult = $this->renderFieldWizard();
        $formField = '<input type="text" ' . GeneralUtility::implodeAttributes([
                'name' => $this->name,
                'value' => $this->value,
                'id' => $this->id,
                'placeholder' => $this->placeholder,
                'class' => 'form-control form-control--tags'
            ], true) . ' />';

        return '
            <div class="form-control-wrap">
                <div class="form-wizards-wrap">
                    <div class="form-wizards-element">' . $formField . '</div>
                    <div class="form-wizards-items-bottom">' . ($fieldWizardResult['html'] ?? '') . '</div>
                </div>
            </div>
        ';
    }

    public function render(): array
    {
        $result = $this->initializeResultArray();

        if ($html = $this->renderHtml()) {
            $result['html'] = $html;
        }

        if ($javaScriptModules = $this->getJavaScriptModule()) {
            $result['javaScriptModules'][] = $javaScriptModules;
        }

        if ($stylesheetFile = $this->getStylesheetFile()) {
            $result['stylesheetFiles'][] = $stylesheetFile;
        }

        return $result;
    }

    public static function register(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1677874287] = [
            'nodeName' => 'rampageTags',
            'priority' => 100,
            'class' => self::class,
        ];
    }
}
