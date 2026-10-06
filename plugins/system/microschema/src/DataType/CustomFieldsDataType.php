<?php

namespace Joomla\Plugin\System\Microschema\DataType;

use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Component\Microschema\Administrator\DataSource\ContextualDataValue;
use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceField;
use Joomla\Component\Microschema\Administrator\DataSource\DataTypeInterface;
use Joomla\Registry\Registry;

final class CustomFieldsDataType implements DataTypeInterface
{
    /** @var array<string, array<int, object|array<string, mixed>>> */
    private array $cache = [];

    public function getName(): string
    {
        return 'JoomlaCustomFields';
    }

    public function getFields(mixed $value, DataContext $context): array
    {
        $fields = [];

        foreach ($this->load($value) as $field) {
            $name = (string) $this->read($field, 'name', '');

            if ($name !== '') {
                $fields[] = new DataSourceField(
                    $name,
                    (string) $this->read($field, 'title', $name),
                    $this->normaliseType((string) $this->read($field, 'type', 'string')),
                );
            }
        }

        return $fields;
    }

    public function resolve(mixed $value, string $field, DataContext $context): mixed
    {
        foreach ($this->load($value) as $customField) {
            if ((string) $this->read($customField, 'name', '') === $field) {
                return $this->read($customField, 'rawvalue', $this->read($customField, 'value'));
            }
        }

        return null;
    }

    /** @return array<int, object|array<string, mixed>> */
    private function load(mixed $value): array
    {
        if (!$value instanceof ContextualDataValue || $value->context === '') {
            return [];
        }

        $key = $value->context.':'.$value->itemId.':'.md5(serialize($value->fieldValues));

        if (!array_key_exists($key, $this->cache)) {
            $fields = FieldsHelper::getFields(
                $value->context,
                $value->value,
                false,
                $value->fieldValues === [] ? null : $value->fieldValues,
            );
            $this->cache[$key] = is_array($fields) ? $fields : [];
        }

        return $this->cache[$key];
    }

    private function read(object|array $field, string $property, mixed $default = null): mixed
    {
        if ($field instanceof Registry) {
            return $field->get($property, $default);
        }

        return is_array($field) ? ($field[$property] ?? $default) : ($field->{$property} ?? $default);
    }

    private function normaliseType(string $type): string
    {
        return match (strtolower($type)) {
            'calendar' => 'DateTime',
            'integer', 'number' => 'Number',
            'url' => 'URL',
            default => 'String',
        };
    }
}
