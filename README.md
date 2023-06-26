# Rampage 🤬

**From now on, record-based detail pages will get a good punch in the face!**

Looking for an extension that does everything for you automatically? Then get the hell out of here! This extension it's
more like a construction kit with many useful tools and functions. Abstract classes and interfaces that help you create
a good structure and save you a lot of work.


## Register new Page-Object

Create new registration in your `ext_localconf.php`. Example:

```php
call_user_func(static function () {
    $object = \Zeroseven\Rampage\Registration\ObjectRegistration::create('Job')
        ->setClassName(\Vendor\NewExtension\Domain\Model\Job::class)
        ->setControllerClass(\Vendor\NewExtension\Controller\JobController::class)
        ->setRepositoryClass(\Vendor\NewExtension\Domain\Repository\JobRepository::class)
        ->setIconIdentifier('custom-job-icon')
        ->enableTop()        // Enable top job feature for job objects
        ->enableTags()       // Enable tag feature for job objects, so tagging and filtering tags is possible
        ->enableTopics(24)   // Enable topics for jobs and give it a pid where to store these
        ->enableContact(24); // Enable responsible contact person for job objects

    $category = \Zeroseven\Rampage\Registration\CategoryRegistration::create('Job-Category')
        ->setClassName(\Vendor\NewExtension\Domain\Model\Category::class)
        ->setRepositoryClass(\Vendor\NewExtension\Domain\Repository\CategoryRepository::class);

    $listPlugin = \Zeroseven\Rampage\Registration\ListPluginRegistration::create('Job list')
        ->setDescription('Display jobs in a super nice list')
        ->setIconIdentifier('custom-joblist-icon');

    $filterPlugin = \Zeroseven\Rampage\Registration\FilterPluginRegistration::create('Job filter')
        ->setDescription('Filter jobs')
        ->setIconIdentifier('custom-joblist-icon');

    \Zeroseven\Rampage\Registration\Registration::create('jobs')
        ->setObject($object)
        ->setCategory($category)
        ->enableListPlugin($listPlugin)
        ->enableFilterPlugin($filterPlugin)
        ->store();
});
```

### Override existing registration

In case you want to override an existing registration the event `BeforeRegistrationEvent` gives you access to all properties, to update them before the rampage extension does the rest.

Alternatively an extension configuration template will be created automatically. Use the settings module of the TYPO3 InstallTool to override the default values.

## Create your own extension

The fast and easy way to create a new Rampage-Extension is to build it from the extension dummy template.

1. Install [cookiecutter](https://cookiecutter.readthedocs.io/en/stable/installation.html#alternate-installations)
2. Run `cookiecutter rampage/Resources/Private/ExtensionDummy`

You will be asked for a view variables like extension key, object name, etc.
After that a new configured extension will be generated for you.

## Extend plugin flexForm

Use the `AddFlexFormEvent` to extend the flexForm of a plugin. Example:

```php
<?php

declare(strict_types=1);

namespace Zeroseven\Jobs\EventListener;

use Zeroseven\Rampage\Registration\Event\AddFlexFormEvent;

class ExtendFlexFormEvent
{
    public function __invoke(AddFlexFormEvent $event)
    {
        $flexFormConfiguration = $event->getFlexFormConfiguration();

        if ($flexFormConfiguration->getType() === 'jobs_list' && $sheet = $flexFormConfiguration->getSheet('filter')) {
            $sheet->addField('settings.customField', [
                'type' => 'input',
                'eval' => 'trim,required'
            ], 'Custom field');
        }
    }
}
```

## Commands and Tasks

Update registration information of category and object pages with the command `rampage:update`. Example:

| Command | Description |
|---------|-------------|
|`rampage:update 7`| Starting from page uid: `7`. |
|`rampage:update 7 2`| Starting from page uid: `7` with depth of `2` levels |

This can be useful if you change the identifier of a registration, or you add pages by API.

## Extend your template

### TypoScript

#### User object

```typo3_typoscript
page.1684843032 = USER
page.1684843032 {
    userFunc = Zeroseven\Rampage\Utility\RenderUtility->renderUserFunc
    file = EXT:my_extension/Resources/Private/Templates/Info.html
    registration = my_registration_identifier, another_registration_identifier
}
```

#### Data processor:

```typo3_typoscript
# Example to show the next object
page.1686075417 = HMENU
page.1686075417 {
  special = browse
  special.items = next

  1 = TMENU
  1.NO {
    doNotLinkIt = 1
    stdWrap.cObject = FLUIDTEMPLATE
    stdWrap.cObject {
      file = EXT:zeroseven_projects/Resources/Private/Templates/Info/Next.html

      dataProcessing.10 = Zeroseven\Rampage\DataProcessing\ObjectProcessor
      dataProcessing.10 {
        uid.data = field:uid
        registration = my_registration_identifier
      }
    }
  }
}
```

#### Custom content element

Create a custom content element to display the object information:

```typo3_typoscript
tt_content.my_content_element =< lib.contentElement
tt_content.my_content_element {
    templateName = MyContentElement
    templateRootPaths.1687792159 = EXT:my_extension/Resources/Private/Templates/Content/

    dataProcessing.1687792159 = Zeroseven\Rampage\DataProcessing\ObjectProcessor
    dataProcessing.1687792159.registration = my_registration_identifier
}
```

## Conditions

### TypoScript
```typo3_typoscript
page.10 = TEXT
page.10.value = It's a normal page.

[my_registration_identifier.object]
page.10.value = Nice! It's an object.
[my_registration_identifier.category]
page.10.value = This is a category page.
[global]
```

### Fluid
```html
<html xmlns:rampage="http://typo3.org/ns/Zeroseven/Rampage/ViewHelpers" data-namespace-typo3-fluid="true">
<rampage:condition.isObject registration="my_registration_identifier">
    <p>Nice! It's an object.</p>
</rampage:condition.isObject>
<rampage:condition.isCategory registration="my_registration_identifier">
    <p>This is a category page.</p>
</rampage:condition.isCategory>
</html>
```
