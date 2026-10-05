<?php

/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') || exit;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Plugin\System\MicroschemaYootheme\Builder\SchemaElementPayloadBuilder;
use Joomla\Plugin\System\MicroschemaYootheme\Builder\SchemaElementRegistrar;

$registrar = new SchemaElementRegistrar(
    Factory::getApplication(),
    new SchemaElementPayloadBuilder(),
);
$listTypes = ['BreadcrumbList', 'ItemList'];
$typeOptions = [Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_SELECT_TYPE') => ''] + $registrar->getTypeOptions($listTypes);
$fieldConfiguration = $registrar->getFieldConfiguration($listTypes);

return [
    'name' => 'microschema_schema',
    'title' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_ELEMENT_TITLE'),
    'group' => 'MicroSchema',
    'element' => true,
    'width' => 500,
    'templates' => [
        'render' => __DIR__.'/templates/template.php',
        'content' => __DIR__.'/templates/content.php',
    ],
    'defaults' => [
        'schema_type' => '',
        'priority' => 0,
    ],
    'fields' => array_merge([
        'schema_type' => [
            'label' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_FIELD_TYPE_LABEL'),
            'description' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_FIELD_TYPE_DESC'),
            'type' => 'select',
            'options' => $typeOptions,
        ],
    ], $fieldConfiguration['fields'], [
        'priority' => [
            'label' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_FIELD_PRIORITY_LABEL'),
            'description' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_FIELD_PRIORITY_DESC'),
            'type' => 'number',
        ],
    ]),
    'fieldset' => [
        'default' => [
            'type' => 'tabs',
            'fields' => [
                [
                    'title' => Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_TAB_SCHEMA'),
                    'fields' => [
                        'schema_type',
                        ...$fieldConfiguration['names'],
                        ...$fieldConfiguration['groups'],
                        'priority',
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
    'transforms' => [
        'render' => static function (object $node) use ($registrar): bool {
            $registrar->register($node);

            return true;
        },
    ],
];
