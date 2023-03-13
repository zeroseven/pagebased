<?php

defined('TYPO3') || die('🤬 Get out of here!');

// Register custom TCA renderType
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1677874287] = [
    'nodeName' => 'rampageTags',
    'priority' => 100,
    'class' => \Zeroseven\Rampage\Backend\Form\Element\TagsElement::class,
];
