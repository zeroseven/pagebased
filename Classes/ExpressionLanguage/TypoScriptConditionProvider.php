<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\ExpressionLanguage;

use TYPO3\CMS\Core\ExpressionLanguage\AbstractProvider;
use Zeroseven\Pagebased\Utility\ObjectUtility;

/**
 * Example:
 *
 * page.10 = TEXT
 * page.10.value = It's a normal page.
 *
 * [my_registration_identifier.object]
 * page.10.value = Nice! It's an object.
 * [global]
 *
 * [my_registration_identifier.myobjectname]
 * page.10.noTrimWrap = || I love it!|
 * [global]
 *
 * [my_registration_identifier.category]
 * page.10.value = This is a category page.
 * [global]
 */
class TypoScriptConditionProvider extends AbstractProvider
{
    public function __construct()
    {
        $object = ObjectUtility::isObject();
        $category = ObjectUtility::isCategory();

        if ($registration = $object ?? $category) {
            $result = new \stdClass();
            $result->object = (bool)$object;
            $result->category = (bool)$category;

            // Alias for object
            $object && $result->{strtolower($registration->getObject()->getName())} = (bool)$object;

            $this->expressionLanguageVariables = [$registration->getIdentifier() => $result];
        }
    }
}
