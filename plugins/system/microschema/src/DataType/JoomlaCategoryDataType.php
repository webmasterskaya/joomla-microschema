<?php

namespace Joomla\Plugin\System\Microschema\DataType;

use Joomla\CMS\Language\Text;
use Joomla\Component\Microschema\Administrator\DataSource\ContextualDataValue;
use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceField;
use Joomla\Component\Microschema\Administrator\DataSource\DataTypeInterface;

final class JoomlaCategoryDataType implements DataTypeInterface
{
    public function getName(): string
    {
        return 'JoomlaCategory';
    }

    public function getFields(mixed $value, DataContext $context): array
    {
        return [
            new DataSourceField('title', Text::_('JGLOBAL_TITLE')),
            new DataSourceField('alias', Text::_('JFIELD_ALIAS_LABEL')),
            new DataSourceField('description', Text::_('JGLOBAL_DESCRIPTION')),
            new DataSourceField('metadesc', Text::_('PLG_SYSTEM_MICROSCHEMA_DATA_FIELD_METADESC')),
            new DataSourceField('link', Text::_('PLG_SYSTEM_MICROSCHEMA_DATA_FIELD_LINK'), 'URL'),
            new DataSourceField('id', Text::_('JGLOBAL_FIELD_ID_LABEL'), 'Integer'),
            new DataSourceField('parent', Text::_('JGLOBAL_FIELD_PARENT_LABEL'), 'JoomlaCategory'),
            new DataSourceField(
                'fields',
                Text::_('PLG_SYSTEM_MICROSCHEMA_DATA_FIELD_CUSTOM_FIELDS'),
                'JoomlaCustomFields',
            ),
        ];
    }

    public function resolve(mixed $value, string $field, DataContext $context): mixed
    {
        if (!$value instanceof ContextualDataValue) {
            return null;
        }

        if ($field === 'fields') {
            return $value;
        }

        if (array_key_exists($field, $value->overrides)) {
            return $value->overrides[$field];
        }

        if ($field === 'parent') {
            $parent = is_object($value->value) && method_exists($value->value, 'getParent')
                ? $value->value->getParent()
                : $this->read($value->value, 'parent');
            $id = (int) $this->read($parent, 'id');

            return $id > 1
                ? new ContextualDataValue($value->context, $id, $parent)
                : null;
        }

        return $this->read($value->value, $field);
    }

    private function read(mixed $value, string $field): mixed
    {
        if (is_array($value)) {
            return $value[$field] ?? null;
        }

        return is_object($value) ? ($value->{$field} ?? null) : null;
    }
}
