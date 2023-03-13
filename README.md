# Rampage 🤬

**From now on, record-based detail pages will get a good punch in the face!**

Looking for an extension that does everything for you automatically? Then get the hell out of here! This extension it's
more like a construction kit with many useful tools and functions. Abstract classes and interfaces that help you create
a good structure and save you a lot of work.


## Register new Page-Object

Create new registration in your `ext_localconf.php`. Example:

```php
call_user_func(static function () {
    $object = \Zeroseven\Rampage\Registration\ObjectRegistration::create('Page-Object')
        ->setClassName(\Vendor\NewExtension\Domain\Model\Job::class)
        ->setControllerClass(\Vendor\NewExtension\Controller\JobController::class)
        ->setRepositoryClass(\Vendor\NewExtension\Domain\Repository\JobRepository::class)
        ->setIconIdentifier('custom-object-icon')
        ->enableTop()
        ->enableTags();

    $category = \Zeroseven\Rampage\Registration\CategoryRegistration::create('Object-Category')
        ->setClassName(\Vendor\NewExtension\Domain\Model\Category::class);

    $listPlugin = \Zeroseven\Rampage\Registration\ListPluginRegistration::create('Object-List')
        ->setDescription('Display objects in a super nice list')
        ->setIconIdentifier('content-bullets');

    $filterPlugin = \Zeroseven\Rampage\Registration\FilterPluginRegistration::create('Object-Filter')
        ->setDescription('Filter objects');

    \Zeroseven\Rampage\Registration\RegistrationService::createRegistration('new_extension')
        ->setObject($object)
        ->enableCategory($category)
        ->enableListPlugin($listPlugin)
        ->enableFilterPlugin($filterPlugin)
        ->store();
});
```
