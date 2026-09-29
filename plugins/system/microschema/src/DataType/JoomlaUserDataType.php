<?php

namespace Joomla\Plugin\System\Microschema\DataType;

use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Component\Microschema\Administrator\DataSource\ContextualDataValue;
use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceField;
use Joomla\Component\Microschema\Administrator\DataSource\DataTypeInterface;

final class JoomlaUserDataType implements DataTypeInterface
{
    public function __construct(private readonly UserFactoryInterface $userFactory)
    {
    }

    public function getName(): string
    {
        return 'JoomlaUser';
    }

    public function getFields(mixed $value, DataContext $context): array
    {
        return [
            new DataSourceField('name', Text::_('JGLOBAL_FIELD_NAME_LABEL')),
            new DataSourceField('username', Text::_('JGLOBAL_USERNAME')),
            new DataSourceField('email', Text::_('JGLOBAL_EMAIL')),
            new DataSourceField('registerDate', Text::_('JGLOBAL_FIELD_CREATED_LABEL'), 'DateTime'),
            new DataSourceField('lastvisitDate', Text::_('JGLOBAL_FIELD_MODIFIED_LABEL'), 'DateTime'),
            new DataSourceField('id', Text::_('JGLOBAL_FIELD_ID_LABEL'), 'Integer'),
            new DataSourceField(
                'fields',
                Text::_('PLG_SYSTEM_MICROSCHEMA_DATA_FIELD_CUSTOM_FIELDS'),
                'JoomlaCustomFields',
            ),
        ];
    }

    public function resolve(mixed $value, string $field, DataContext $context): mixed
    {
        if ($field === 'fields') {
            return $value instanceof ContextualDataValue ? $value : null;
        }

        $overrides = $value instanceof ContextualDataValue ? $value->overrides : [];

        if (array_key_exists($field, $overrides)) {
            return $overrides[$field];
        }

        $value = $value instanceof ContextualDataValue ? $value->value : $value;
        $local = $this->read($value, $field);

        if ($local !== null && $local !== '') {
            return $local;
        }

        $id = (int) ($this->read($value, 'id') ?? (is_numeric($value) ? $value : 0));

        if ($id < 1) {
            return null;
        }

        $user = $value instanceof User ? $value : $this->userFactory->loadUserById($id);

        return $user->{$field} ?? null;
    }

    private function read(mixed $value, string $field): mixed
    {
        if (is_array($value)) {
            return $value[$field] ?? null;
        }

        return is_object($value) ? ($value->{$field} ?? null) : null;
    }
}
