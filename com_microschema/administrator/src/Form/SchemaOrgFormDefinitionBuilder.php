<?php

namespace Joomla\Component\Microschema\Administrator\Form;

use Joomla\CMS\Language\Text;
use Joomla\Component\Microschema\Administrator\Metadata\DescriptorInterface;
use Joomla\Component\Microschema\Administrator\Metadata\PropertyDefinition;
use Joomla\Registry\Registry;

final class SchemaOrgFormDefinitionBuilder
{
    /** @param array<string, class-string<DescriptorInterface>> $schemas */
    public function __construct(private readonly array $schemas)
    {
    }

    /**
     * @return array{
     *     schemas: array<string, array{name: string, properties: list<array<string, mixed>>}>,
     *     value: mixed
     * }
     */
    public function build(mixed $value): array
    {
        $definitions = [];

        foreach ($this->schemas as $name => $descriptorClass) {
            $descriptor = new $descriptorClass();
            $properties = [];

            foreach ($descriptor->getProperties() as $property) {
                if ($property->automatic) {
                    continue;
                }

                $properties[] = $this->buildProperty($property);
            }

            $definitions[$name] = [
                'name' => $descriptor->getName(),
                'properties' => $properties,
            ];
        }

        return [
            'schemas' => $definitions,
            'value' => $this->normalizeValue($value),
        ];
    }

    /** @return array<string, mixed> */
    private function buildProperty(PropertyDefinition $property): array
    {
        $types = [];
        $options = [];

        foreach ($property->types as $type) {
            $types[] = [
                'name' => $type,
                'kind' => $this->getKind($type),
                'input' => $this->getInputType($type),
            ];
        }

        foreach ($property->options as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => Text::_($label),
            ];
        }

        return [
            'name' => $property->name,
            'label' => $property->name,
            'description' => implode(', ', $property->types),
            'required' => $property->required,
            'multiple' => $property->multiple,
            'options' => $options,
            'types' => $types,
        ];
    }

    private function getKind(string $type): string
    {
        if ($type === '@id') {
            return 'reference';
        }

        return isset($this->schemas[$type]) ? 'object' : 'scalar';
    }

    private function getInputType(string $type): string
    {
        return match ($type) {
            'Boolean' => 'boolean',
            'Date' => 'calendar-date',
            'DateTime' => 'calendar-datetime',
            'Integer', 'Number' => 'number',
            'URL' => 'url',
            default => 'text',
        };
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof Registry) {
            $value = $value->toArray();
        } elseif (is_object($value)) {
            $value = get_object_vars($value);
        }

        if (!is_array($value)) {
            return is_scalar($value) || $value === null ? $value : null;
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            $normalized[$key] = $this->normalizeValue($item);
        }

        return $normalized;
    }
}
