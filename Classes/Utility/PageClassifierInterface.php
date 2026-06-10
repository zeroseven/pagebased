<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Utility;

use Zeroseven\Pagebased\Registration\Registration;

interface PageClassifierInterface
{
    public function isObject(?int $pageUid = null, ?array $row = null): ?Registration;

    public function isCategory(?int $pageUid = null, ?array $row = null): ?Registration;

    public function isSystemPage(?int $pageUid = null, ?array $row = null): bool;

    public function isChildObject(mixed $uid): ?Registration;

    public function findCategoryInRootLine(mixed $startPoint): ?Registration;
}
