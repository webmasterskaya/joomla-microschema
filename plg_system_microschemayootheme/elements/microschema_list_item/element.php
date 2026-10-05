<?php

/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') || exit;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Plugin\System\MicroschemaYootheme\Builder\SchemaListPayloadBuilder;
use Joomla\Plugin\System\MicroschemaYootheme\Builder\SchemaListRegistrar;

$registrar = new SchemaListRegistrar(
    Factory::getApplication(),
    new SchemaListPayloadBuilder(),
);
$itemTypeOptions = [Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_LIST_ITEM_SELECT_TYPE') => '']
    + $registrar->getItemTypeOptions();
$fieldConfiguration = $registrar->getItemFieldConfiguration();

return [
    'name' => 'microschema_list_item',
    'title' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_LIST_ITEM_TITLE'),
    'width' => 500,
    'templates' => [
        'render' => __DIR__.'/templates/template.php',
        'content' => __DIR__.'/templates/content.php',
    ],
    'fields' => array_merge([
        'title' => [
            'label' => 'name',
            'source' => true,
        ],
        'url' => [
            'label' => 'url',
            'type' => 'link',
            'source' => true,
        ],
        'item_type' => [
            'label' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_LIST_ITEM_FIELD_TYPE_LABEL'),
            'description' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_LIST_ITEM_FIELD_TYPE_DESC'),
            'type' => 'select',
            'options' => $itemTypeOptions,
        ],
        'item_url' => [
            'label' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_LIST_ITEM_FIELD_URL_LABEL'),
            'type' => 'link',
            'source' => true,
            'show' => 'item_type=="URL"',
        ],
        'item_id' => [
            'label' => '@id',
            'description' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_LIST_ITEM_FIELD_ID_DESC'),
            'source' => true,
            'show' => 'item_type=="BlogPosting" || item_type=="NewsArticle"',
        ],
    ], $fieldConfiguration['fields']),
    'fieldset' => [
        'default' => [
            'type' => 'tabs',
            'fields' => [
                [
                    'title' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_TAB_SCHEMA'),
                    'fields' => [
                        'title',
                        'url',
                        'item_type',
                        'item_url',
                        'item_id',
                        ...$fieldConfiguration['names'],
                        ...$fieldConfiguration['groups'],
                    ],
                ],
                [
                    'title' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_TAB_ADVANCED'),
                    'fields' => [
                        'source',
                        'status',
                    ],
                ],
            ],
        ],
    ],
];
