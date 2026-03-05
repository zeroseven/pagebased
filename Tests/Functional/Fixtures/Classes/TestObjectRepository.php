<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Tests\Functional\Fixtures\Classes;

use Zeroseven\Pagebased\Domain\Repository\AbstractObjectRepository;

/**
 * Minimal test object repository – registered in test setUp() via RegistrationService.
 */
class TestObjectRepository extends AbstractObjectRepository {}
