<?php

/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\MicroschemaYootheme\Builder;

defined('_JEXEC') || exit;

use Joomla\CMS\Language\Text;
use Joomla\Component\Microschema\Administrator\Metadata\DescriptorInterface;
use Joomla\Component\Microschema\Administrator\Metadata\PropertyDefinition;

final class SchemaElementFieldBuilder
{
    private const LONG_TEXT_PROPERTIES = [
        'articleBody',
        'description',
        'recipeInstructions',
        'reviewBody',
        'text',
    ];

    /** @var array<string, class-string<DescriptorInterface>> */
    private readonly array $schemas;

    private readonly string $selectorName;

    private readonly string $fieldPrefix;

    /**
     * @param array<string, class-string<DescriptorInterface>> $schemas
     * @param list<string>                                     $excludedTypes
     */
    public function __construct(
        array $schemas,
        array $excludedTypes = [],
        string $selectorName = 'schema_type',
        string $fieldPrefix = 'schema',
    ) {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $selectorName)
            || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $fieldPrefix)) {
            throw new \InvalidArgumentException('Schema element field identifiers are invalid.');
        }

        $this->schemas = array_diff_key($schemas, array_fill_keys($excludedTypes, true));
        $this->selectorName = $selectorName;
        $this->fieldPrefix = $fieldPrefix;
    }

    /**
     * @return array{
     *     fields: array<string, array<string, mixed>>,
     *     names: list<string>,
     *     groups: list<array{label: string, type: string, divider: bool, fields: list<string>, show: string}>
     * }
     */
    public function build(): array
    {
        $fields = [];
        $names = [];
        $groups = [];

        foreach ($this->schemas as $schemaType => $descriptorClass) {
            $descriptor = new $descriptorClass();

            foreach ($descriptor->getProperties() as $property) {
                if ($property->automatic) {
                    continue;
                }

                $name = $this->getFieldName($schemaType, $property->name);
                $fields[$name] = $this->buildField($schemaType, $property);
                if ($this->objectTypes($property) !== []) {
                    $objectFields = $this->buildObjectFields($name, $fields[$name], $property);
                    unset($fields[$name]);
                    $fields += $objectFields;

                    $group = $groups[$property->name] ?? [
                        'label' => $property->name,
                        'fields' => [],
                        'conditions' => [],
                    ];
                    $group['fields'] = [...$group['fields'], ...array_keys($objectFields)];
                    $group['conditions'][] = $this->selectorName.'=='.json_encode($schemaType, JSON_THROW_ON_ERROR);
                    $groups[$property->name] = $group;

                    continue;
                }

                $names[] = $name;
            }
        }

        $groups = array_values(array_map(
            static fn (array $group): array => [
                'label' => $group['label'],
                'type' => 'group',
                'divider' => true,
                'fields' => $group['fields'],
                'show' => implode(' || ', array_values(array_unique($group['conditions']))),
            ],
            $groups,
        ));

        return [
            'fields' => $fields,
            'names' => $names,
            'groups' => $groups,
        ];
    }

    /** @param array<string, mixed> $props */
    public function hasSubmittedFields(string $schemaType, array $props): bool
    {
        $descriptor = $this->getDescriptor($schemaType);

        if ($descriptor === null) {
            return false;
        }

        foreach ($descriptor->getProperties() as $property) {
            if (!$property->automatic && $this->hasPropertyFields($this->getFieldName($schemaType, $property->name), $props)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $props
     *
     * @return array<string, mixed>
     */
    public function extractProperties(string $schemaType, array $props): array
    {
        $descriptor = $this->getDescriptor($schemaType);

        if ($descriptor === null) {
            return [];
        }

        $properties = [];

        foreach ($descriptor->getProperties() as $property) {
            if ($property->automatic) {
                continue;
            }

            $name = $this->getFieldName($schemaType, $property->name);

            if (!$this->hasPropertyFields($name, $props)) {
                continue;
            }

            $value = $this->objectTypes($property) !== []
                ? $this->extractObjectValue($name, $props, $property)
                : $this->normalizeValue($props[$name] ?? null, $property);

            if ($value !== null && $value !== '' && $value !== []) {
                $properties[$property->name] = $value;
            }
        }

        return $properties;
    }

    public function getFieldName(string $schemaType, string $propertyName): string
    {
        return $this->fieldPrefix.'_'.strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '_', $schemaType))
            .'_'.strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $propertyName));
    }

    private function objectTypes(PropertyDefinition $property): array
    {
        $supported = match ($property->name) {
            'author', 'publisher' => ['Person', 'Organization'],
            'image', 'logo' => ['ImageObject'],
            'mainEntityOfPage' => ['WebPage'],
            default => [],
        };

        return array_values(array_intersect($supported, $property->types));
    }

    private function hasPropertyFields(string $name, array $props): bool
    {
        foreach (array_keys($props) as $key) {
            if ($key === $name || str_starts_with((string) $key, $name.'__')) {
                return true;
            }
        }

        return false;
    }

    private function buildObjectFields(string $name, array $base, PropertyDefinition $property): array
    {
        $image = in_array('ImageObject', $this->objectTypes($property), true);
        $webPage = in_array('WebPage', $this->objectTypes($property), true);
        $mode = $name.'__mode';
        $options = [Text::_($image
            ? 'PLG_SYSTEM_MICROSCHEMAYOOTHEME_VALUE_IMAGE'
            : ($webPage
                ? 'PLG_SYSTEM_MICROSCHEMAYOOTHEME_VALUE_URL'
                : 'PLG_SYSTEM_MICROSCHEMAYOOTHEME_VALUE_REFERENCE')) => ''];
        if (($image || $webPage) && in_array('@id', $property->types, true)) {
            $options[Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_VALUE_REFERENCE')] = '@id';
        }
        foreach ($this->objectTypes($property) as $type) {
            $options[$type] = $type;
        }
        $fields = [$mode => [
            'label' => $property->name.' — '.Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_VALUE_MODE'),
            'type' => 'select',
            'options' => $options,
            'show' => $base['show'],
        ]];
        $base['show'] .= ' && !'.$mode;
        $base['type'] = $image ? 'image' : ($webPage ? 'link' : 'text');
        unset($base['attrs']);
        $base['description'] = Text::_($image
            ? 'PLG_SYSTEM_MICROSCHEMAYOOTHEME_VALUE_IMAGE_DESC'
            : ($webPage
                ? 'PLG_SYSTEM_MICROSCHEMAYOOTHEME_VALUE_URL_DESC'
                : 'PLG_SYSTEM_MICROSCHEMAYOOTHEME_VALUE_REFERENCE_DESC'));
        $fields[$name] = $base;
        $show = $fields[$mode]['show'];
        $objectCondition = implode(' || ', array_map(
            static fn (string $type): string => $mode.'=='.json_encode($type, JSON_THROW_ON_ERROR),
            $this->objectTypes($property),
        ));
        $parts = $image
            ? ['id' => 'text', 'url' => 'image', 'caption' => 'textarea', 'width' => 'number', 'height' => 'number']
            : ($webPage
                ? ['id' => 'text', 'name' => 'text', 'url' => 'link', 'description' => 'textarea']
                : ['id' => 'text', 'name' => 'text', 'url' => 'link', 'image' => 'image']);
        foreach ($parts as $part => $fieldType) {
            $fields[$name.'__'.$part] = [
                'label' => $property->name.'.'.($part === 'id' ? '@id' : $part),
                'type' => $fieldType,
                'source' => true,
                'show' => $show.' && ('.$objectCondition.($part === 'id' ? ' || '.$mode.'=="@id"' : '').')',
            ];
        }

        return $fields;
    }

    private function extractObjectValue(string $name, array $props, PropertyDefinition $property): mixed
    {
        $mode = $props[$name.'__mode'] ?? '';
        $image = in_array('ImageObject', $this->objectTypes($property), true);
        $webPage = in_array('WebPage', $this->objectTypes($property), true);
        if ($mode === '') {
            $value = $props[$name] ?? null;
            // Dynamic sources and older layouts may already provide an object.
            if (is_array($value) && !array_is_list($value)) {
                $value = [$value];
            } elseif (is_string($value)) {
                $value = $this->splitMultipleValue($value);
            } elseif (!is_array($value)) {
                $value = [$value];
            }
            $items = [];
            foreach ($value as $item) {
                $item = is_string($item) ? trim($item) : $item;
                if ($item === null || $item === '' || $item === []) {
                    continue;
                }
                $items[] = !$image && !$webPage && is_string($item) ? ['@id' => $item] : $item;
            }

            return $property->multiple ? $items : ($items[0] ?? null);
        }
        if (!in_array($mode, [...$this->objectTypes($property), '@id'], true)) {
            return null;
        }
        $object = [];
        $parts = $mode === '@id' ? ['id'] : ($image
            ? ['id', 'url', 'caption', 'width', 'height']
            : ($webPage
                ? ['id', 'name', 'url', 'description']
                : ['id', 'name', 'url', 'image']));
        foreach ($parts as $part) {
            $value = $props[$name.'__'.$part] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            if (in_array($part, ['width', 'height'], true)) {
                if (!is_numeric($value) || (int) $value <= 0) {
                    continue;
                }
                $value = (int) $value;
            }
            $object[$part === 'id' ? '@id' : $part] = $value;
        }
        if ($object === []) {
            return null;
        }
        if ($mode !== '@id') {
            $object = ['@type' => $mode] + $object;
        }

        return $property->multiple ? [$object] : $object;
    }

    /** @return array<string, mixed> */
    private function buildField(string $schemaType, PropertyDefinition $property): array
    {
        $description = [Text::sprintf(
            'PLG_SYSTEM_MICROSCHEMAYOOTHEME_FIELD_PROPERTY_TYPES',
            implode(', ', $property->types),
        )];

        if ($property->required) {
            $description[] = Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_FIELD_PROPERTY_REQUIRED');
        }

        if ($property->multiple) {
            $description[] = Text::_('PLG_SYSTEM_MICROSCHEMAYOOTHEME_FIELD_PROPERTY_MULTIPLE');
        }

        $field = [
            'label' => $property->name.($property->required ? ' *' : ''),
            'description' => implode(' ', $description),
            'type' => $this->getFieldType($property),
            'source' => true,
            'show' => $this->selectorName.'=='.json_encode($schemaType, JSON_THROW_ON_ERROR),
        ];

        if ($field['type'] === 'textarea') {
            $field['attrs'] = ['rows' => $property->multiple ? 4 : 6];
        }

        if ($field['type'] === 'select' && $property->types === ['Boolean']) {
            $field['options'] = [
                Text::_('JNONE') => '',
                Text::_('JYES') => '1',
                Text::_('JNO') => '0',
            ];
        }

        return $field;
    }

    private function getFieldType(PropertyDefinition $property): string
    {
        if ($property->multiple) {
            return 'textarea';
        }

        if ($property->types === ['Boolean']) {
            return 'select';
        }

        if ($property->types !== [] && array_diff($property->types, ['Integer', 'Number']) === []) {
            return 'number';
        }

        if (in_array($property->name, self::LONG_TEXT_PROPERTIES, true)) {
            return 'textarea';
        }

        return 'text';
    }

    private function normalizeValue(mixed $value, PropertyDefinition $property): mixed
    {
        if ($property->multiple) {
            if (is_string($value)) {
                $value = $this->splitMultipleValue($value);
            } elseif (!is_array($value)) {
                $value = $value === null || $value === '' ? [] : [$value];
            }

            return array_values(array_filter($value, static fn (mixed $item): bool => $item !== null && $item !== ''));
        }

        if ($property->types === ['Boolean']) {
            if ($value === null || $value === '') {
                return null;
            }

            if (is_bool($value)) {
                return $value;
            }

            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        if ($property->types !== [] && array_diff($property->types, ['Integer', 'Number']) === [] && is_numeric($value)) {
            return in_array('Number', $property->types, true) ? (float) $value : (int) $value;
        }

        return $value;
    }

    /** @return list<string> */
    private function splitMultipleValue(string $value): array
    {
        $items = preg_split('/(?:\R+|\h*\|\|\h*)/u', trim($value)) ?: [];

        return array_map('trim', $items);
    }

    private function getDescriptor(string $schemaType): ?DescriptorInterface
    {
        $descriptorClass = $this->schemas[$schemaType] ?? null;

        return $descriptorClass === null ? null : new $descriptorClass();
    }
}
