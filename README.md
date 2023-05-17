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

    \Zeroseven\Rampage\Registration\RegistrationService::createRegistration('jobs')
        ->setObject($object)
        ->setCategory($category)
        ->enableListPlugin($listPlugin)
        ->enableFilterPlugin($filterPlugin)
        ->store();
});
```

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
