<?php

namespace Joomla\Plugin\System\Microschema\DataType;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceField;
use Joomla\Component\Microschema\Administrator\DataSource\DataTypeInterface;
use Joomla\Registry\Registry;

final class JoomlaMenuItemDataType implements DataTypeInterface
{
    private const PARAM_FIELDS = [
        'page_heading',
        'page_title',
        'menu-meta_description',
        'menu_image',
    ];

    public function getName(): string
    {
        return 'JoomlaMenuItem';
    }

    public function getFields(mixed $value, DataContext $context): array
    {
        return [
            new DataSourceField('title', Text::_('PLG_SYSTEM_MICROSCHEMA_DATA_FIELD_MENU_TITLE')),
            new DataSourceField('alias', Text::_('JFIELD_ALIAS_LABEL')),
            new DataSourceField('link', Text::_('PLG_SYSTEM_MICROSCHEMA_DATA_FIELD_LINK'), 'URL'),
            new DataSourceField('page_heading', Text::_('PLG_SYSTEM_MICROSCHEMA_DATA_FIELD_PAGE_HEADING')),
            new DataSourceField('page_title', Text::_('PLG_SYSTEM_MICROSCHEMA_DATA_FIELD_BROWSER_TITLE')),
            new DataSourceField('menu-meta_description', Text::_('PLG_SYSTEM_MICROSCHEMA_DATA_FIELD_METADESC')),
            new DataSourceField('menu_image', Text::_('PLG_SYSTEM_MICROSCHEMA_DATA_FIELD_MENU_IMAGE')),
            new DataSourceField('menutype', Text::_('PLG_SYSTEM_MICROSCHEMA_DATA_FIELD_MENU_TYPE')),
            new DataSourceField('type', Text::_('PLG_SYSTEM_MICROSCHEMA_DATA_FIELD_MENU_ITEM_TYPE')),
            new DataSourceField('language', Text::_('JFIELD_LANGUAGE_LABEL')),
            new DataSourceField('level', Text::_('PLG_SYSTEM_MICROSCHEMA_DATA_FIELD_MENU_LEVEL'), 'Integer'),
            new DataSourceField('home', Text::_('PLG_SYSTEM_MICROSCHEMA_DATA_FIELD_MENU_HOME'), 'Boolean'),
            new DataSourceField('parent', Text::_('JGLOBAL_FIELD_PARENT_LABEL'), 'JoomlaMenuItem'),
            new DataSourceField('id', Text::_('JGLOBAL_FIELD_ID_LABEL'), 'Integer'),
        ];
    }

    public function resolve(mixed $value, string $field, DataContext $context): mixed
    {
        if (!is_object($value) && !is_array($value)) {
            return null;
        }

        if (in_array($field, self::PARAM_FIELDS, true)) {
            $resolved = $this->parameter($value, $field);

            return $field === 'menu_image' && $resolved === '-1' ? null : $resolved;
        }

        return match ($field) {
            'link' => $this->link($value),
            'parent' => $this->parent($value),
            'home' => (bool) $this->read($value, 'home'),
            default => $this->read($value, $field),
        };
    }

    private function link(object|array $item): ?string
    {
        $id = (int) ($this->read($item, 'id') ?? 0);

        return $id > 0
            ? Route::_('index.php?Itemid='.$id, true, 0, true)
            : null;
    }

    private function parent(object|array $item): mixed
    {
        $parent = is_object($item) && method_exists($item, 'getParent')
            ? $item->getParent()
            : $this->read($item, 'parent');

        return (int) $this->read($parent, 'id') > 1 ? $parent : null;
    }

    private function parameter(object|array $item, string $name): mixed
    {
        $params = is_object($item) && method_exists($item, 'getParams')
            ? $item->getParams()
            : $this->read($item, 'params');

        if ($params instanceof Registry) {
            return $params->get($name);
        }

        return $this->read($params, $name);
    }

    private function read(mixed $value, string $field): mixed
    {
        if (is_array($value)) {
            return $value[$field] ?? null;
        }

        if ($value instanceof Registry) {
            return $value->get($field);
        }

        return is_object($value) ? ($value->{$field} ?? null) : null;
    }
}
