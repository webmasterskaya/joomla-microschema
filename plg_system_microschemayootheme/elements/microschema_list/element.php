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

return [
    'name' => 'microschema_list',
    'title' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_LIST_ELEMENT_TITLE'),
    'group' => 'MicroSchema',
    'element' => true,
    'container' => true,
    'width' => 500,
    'templates' => [
        'render' => __DIR__.'/templates/template.php',
        'content' => __DIR__.'/templates/content.php',
    ],
    'defaults' => [
        'schema_type' => 'ItemList',
        'priority' => 0,
    ],
    'fields' => [
        'schema_type' => [
            'label' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_FIELD_TYPE_LABEL'),
            'description' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_LIST_FIELD_TYPE_DESC'),
            'type' => 'select',
            'options' => [
                'ItemList' => 'ItemList',
                'BreadcrumbList' => 'BreadcrumbList',
            ],
        ],
        'schema_id' => [
            'label' => '@id',
            'description' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_LIST_ID_DESC'),
            'source' => true,
            'show' => 'schema_type=="ItemList"',
        ],
        'content' => [
            'label' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_LIST_FIELD_ITEMS_LABEL'),
            'type' => 'content-items',
            'item' => 'microschema_list_item',
        ],
        'list_name' => [
            'label' => 'name',
            'source' => true,
            'show' => 'schema_type=="ItemList"',
        ],
        'item_list_order' => [
            'label' => 'itemListOrder',
            'description' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_LIST_FIELD_ORDER_DESC'),
            'type' => 'select',
            'options' => [
                Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_LIST_ORDER_UNORDERED') => 'https://schema.org/ItemListUnordered',
                Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_LIST_ORDER_ASCENDING') => 'https://schema.org/ItemListOrderAscending',
                Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_LIST_ORDER_DESCENDING') => 'https://schema.org/ItemListOrderDescending',
            ],
            'source' => true,
            'show' => 'schema_type=="ItemList"',
        ],
        'priority' => [
            'label' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_FIELD_PRIORITY_LABEL'),
            'description' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_FIELD_PRIORITY_DESC'),
            'type' => 'number',
        ],
    ],
    'fieldset' => [
        'default' => [
            'type' => 'tabs',
            'fields' => [
                [
                    'title' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_TAB_SCHEMA'),
                    'fields' => [
                        'schema_type',
                        'schema_id',
                        'content',
                        'list_name',
                        'item_list_order',
                        'priority',
                    ],
                ],
                [
                    'title' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_TAB_ADVANCED'),
                    'fields' => [
                        'name',
                        'source',
                        'status',
                    ],
                ],
            ],
        ],
    ],
    'transforms' => [
        'render' => static function (object $node) use ($registrar): bool {
            $registrar->register($node);

            return true;
        },
    ],
];
