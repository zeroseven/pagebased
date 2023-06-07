<?php

return [
    'apps-pagetree-page-{{ cookiecutter.object_name|lower }}' => [
        'provider' => \Zeroseven\Rampage\Imaging\IconProvider\AppIconProvider::class,
        'registration' => '{{ cookiecutter.extension_key }}',
        'hideInMenu' => false
    ],
    'apps-pagetree-page-{{ cookiecutter.object_name|lower }}-hideinmenu' => [
        'provider' => \Zeroseven\Rampage\Imaging\IconProvider\AppIconProvider::class,
        'registration' => '{{ cookiecutter.extension_key }}',
        'hideInMenu' => true
    ]
];
