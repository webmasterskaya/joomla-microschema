<?php

defined('_JEXEC') || exit;

use Joomla\CMS\Layout\LayoutHelper;

$displayData = [
    'textPrefix' => 'COM_MICROSCHEMA_DASHBOARD',
    'formURL' => 'index.php?option=com_microschema',
    'createURL' => 'index.php?option=com_config&view=component&component=com_microschema',
    'icon' => 'icon-options',
];

echo LayoutHelper::render('joomla.content.emptystate', $displayData);
