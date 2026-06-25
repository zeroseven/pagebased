<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Tests\Functional\Registration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Zeroseven\Pagebased\Registration\Registration;
use Zeroseven\Pagebased\Registration\RegistrationService;

/**
 * Boots a real consumer registration (RegistrationFixture extension) that calls
 * Registration::create()->...->enableListPlugin()->store() in ext_localconf.php and verifies the
 * whole registration pipeline survives on the loaded TYPO3 version.
 *
 * This guards the cross-version (v13 + v14) registration glue that has no other coverage:
 *  - the boot itself succeeds, i.e. IconRegistry registration (BootCompletedEvent), the page/user
 *    TSConfig events and ValidateRegistrationEvent do not throw;
 *  - the list plugin is registered as a content type (CType) in the TCA;
 *  - the FlexForm data structure is bound to that CType (v13 "ds" pointer array vs v14
 *    columnsOverrides);
 *  - the category documentType is registered as a page type.
 */
class RegistrationBootTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/pagebased',
        'typo3conf/ext/pagebased/Tests/Functional/RegistrationFixture',
    ];

    protected array $coreExtensionsToLoad = [
        'core',
        'backend',
        'frontend',
        'extbase',
        'fluid',
    ];

    private function registration(): Registration
    {
        $registration = RegistrationService::getRegistrationByIdentifier('RegistrationFixture');
        self::assertInstanceOf(
            Registration::class,
            $registration,
            'The fixture registration must be stored during boot (ext_localconf.php → Registration::store()).'
        );

        return $registration;
    }

    #[Test]
    public function registrationIsStoredAndValidatedOnBoot(): void
    {
        // Reaching this point means store() + AfterTcaCompilationEvent + BootCompletedEvent
        // (icons, TSConfig, validation) ran without throwing.
        $registration = $this->registration();

        self::assertTrue($registration->hasListPlugin(), 'The registration must expose its list plugin.');
        self::assertSame(188, $registration->getCategory()->getDocumentType());
    }

    #[Test]
    public function listPluginCTypeIsRegisteredInTca(): void
    {
        $registration = $this->registration();
        $cType = $registration->getListPlugin()->getCType($registration);

        self::assertArrayHasKey(
            $cType,
            $GLOBALS['TCA']['tt_content']['types'] ?? [],
            sprintf('The list plugin CType "%s" must be registered as a tt_content type.', $cType)
        );
    }

    #[Test]
    public function flexFormDataStructureIsRegisteredForCType(): void
    {
        $registration = $this->registration();
        $cType = $registration->getListPlugin()->getCType($registration);

        $columnDs = $GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds'] ?? null;
        $typeDs = $GLOBALS['TCA']['tt_content']['types'][$cType]['columnsOverrides']['pi_flexform']['config']['ds'] ?? null;

        // v13 registers the data structure under the "*,<CType>" pointer in the column's "ds" array;
        // v14 binds it to the CType via columnsOverrides. Either is acceptable.
        $registered = (is_array($columnDs) && ($columnDs['*,' . $cType] ?? '') !== '')
            || (is_string($typeDs) && $typeDs !== '');

        self::assertTrue(
            $registered,
            sprintf('A FlexForm data structure must be registered for the plugin CType "%s".', $cType)
        );
    }

    #[Test]
    public function categoryDocumentTypeIsRegisteredAsPageType(): void
    {
        $documentType = $this->registration()->getCategory()->getDocumentType();

        self::assertArrayHasKey(
            $documentType,
            $GLOBALS['TCA']['pages']['types'] ?? [],
            sprintf('The category documentType "%d" must be registered as a pages type.', $documentType)
        );
    }
}
