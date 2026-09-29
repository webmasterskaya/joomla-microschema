<?php

namespace Joomla\Plugin\System\Microschema\DataType;

use Joomla\CMS\Language\Text;
use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceField;
use Joomla\Component\Microschema\Administrator\DataSource\DataTypeInterface;
use Joomla\Registry\Registry;

final class SiteDataType implements DataTypeInterface
{
    public function getName(): string
    {
        return 'JoomlaSite';
    }

    public function getFields(mixed $value, DataContext $context): array
    {
        return [
            new DataSourceField('sitename', Text::_('COM_MICROSCHEMA_DATA_SOURCE_SITE_NAME')),
            new DataSourceField('MetaDesc', Text::_('COM_MICROSCHEMA_DATA_SOURCE_SITE_DESCRIPTION')),
            new DataSourceField('currentUrl', Text::_('COM_MICROSCHEMA_DATA_SOURCE_SITE_CURRENT_URL'), 'URL'),
            new DataSourceField('baseUrl', Text::_('COM_MICROSCHEMA_DATA_SOURCE_SITE_BASE_URL'), 'URL'),
        ];
    }

    public function resolve(mixed $value, string $field, DataContext $context): mixed
    {
        if (!in_array($field, ['sitename', 'MetaDesc', 'currentUrl', 'baseUrl'], true)) {
            return null;
        }

        if ($value instanceof Registry) {
            return $value->get($field);
        }

        if (is_array($value)) {
            return $value[$field] ?? null;
        }

        return is_object($value) ? ($value->{$field} ?? null) : null;
    }
}
