<?php

namespace Joomla\Component\Microschema\Administrator\Schema;

use Joomla\Component\Microschema\Administrator\Metadata\DescriptorInterface;
use Joomla\Component\Microschema\Administrator\Metadata\PropertyDefinition;

final class SchemaDataBuilder
{
    private const REFERENCE_TYPE = '@id';

    /** @var array<string, true> */
    private array $objectTypes;

    /** @var array<string, array<string, PropertyDefinition>> */
    private array $properties = [];

    /**
     * @param list<string>|array<string, class-string<DescriptorInterface>> $schemas
     */
    public function __construct(array $schemas)
    {
        $this->objectTypes = array_fill_keys(array_is_list($schemas) ? $schemas : array_keys($schemas), true);

        if (array_is_list($schemas)) {
            return;
        }

        foreach ($schemas as $schemaType => $descriptorClass) {
            $descriptor = new $descriptorClass();

            foreach ($descriptor->getProperties() as $property) {
                $this->properties[$schemaType][$property->name] = $property;
            }
        }
    }

    /** @return array<string, mixed> */
    public function build(string $schemaType, mixed $properties): array
    {
        $schemaType = trim($schemaType);

        if ($schemaType === '') {
            return [];
        }

        $schema = ['@type' => $schemaType];

        foreach ($this->toArray($properties) as $name => $value) {
            $value = $this->normalize($value, $this->properties[$schemaType][(string) $name] ?? null);

            if (!$this->isEmpty($value)) {
                $schema[(string) $name] = $value;
            }
        }

        return $schema;
    }

    private function normalize(mixed $value, ?PropertyDefinition $property = null): mixed
    {
        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        if (!is_array($value)) {
            return $this->normalizeScalar($value, $property?->types ?? []);
        }

        if ($this->isTypedValue($value)) {
            return $this->normalizeTypedValue($value);
        }

        if ($this->isTypedValueCollection($value)) {
            $result = [];

            foreach ($value as $item) {
                $typedValue = $this->toArray($item);
                $normalized = $this->normalizeTypedValue($typedValue);

                if (!$this->isEmpty($normalized)) {
                    $type = trim((string) ($typedValue['type'] ?? ''));

                    if (is_array($normalized)
                        && array_is_list($normalized)
                        && $type !== self::REFERENCE_TYPE
                        && !isset($this->objectTypes[$type])) {
                        array_push($result, ...$normalized);
                    } else {
                        $result[] = $normalized;
                    }
                }
            }

            return $result;
        }

        $result = [];

        foreach ($value as $name => $item) {
            $normalized = $this->normalize($item);

            if (!$this->isEmpty($normalized)) {
                $result[$name] = $normalized;
            }
        }

        return array_is_list($value) ? array_values($result) : $result;
    }

    /** @param array<string|int, mixed> $value */
    private function normalizeTypedValue(array $value): mixed
    {
        $type = trim((string) ($value['type'] ?? ''));
        $data = $value['data'] ?? null;

        if ($type !== '' && isset($this->properties[$type])) {
            $data = $this->normalizeObject($type, $this->toArray($data));
        } else {
            $data = $this->normalizeScalar($this->normalize($data), [$type]);
        }

        if ($this->isEmpty($data)) {
            return null;
        }

        if ($type === self::REFERENCE_TYPE && is_scalar($data)) {
            return [self::REFERENCE_TYPE => $data];
        }

        if ($type !== '' && isset($this->objectTypes[$type]) && is_array($data) && !array_is_list($data)) {
            return ['@type' => $type] + $data;
        }

        return $data;
    }

    /** @param array<string|int, mixed> $value */
    private function normalizeObject(string $schemaType, array $value): array
    {
        $result = [];

        foreach ($value as $name => $item) {
            $normalized = $this->normalize($item, $this->properties[$schemaType][(string) $name] ?? null);

            if (!$this->isEmpty($normalized)) {
                $result[$name] = $normalized;
            }
        }

        return $result;
    }

    /** @param list<string> $types */
    private function normalizeScalar(mixed $value, array $types): mixed
    {
        if (!is_scalar($value) || (is_string($value) && str_contains($value, '{'))) {
            return $value;
        }

        if ($types === ['Integer']) {
            $integer = filter_var($value, FILTER_VALIDATE_INT);

            return $integer !== false ? $integer : $value;
        }

        if ($types === ['Number'] && is_numeric($value)) {
            $number = (float) $value;

            return is_finite($number) ? $number : $value;
        }

        if ($types === ['Boolean'] && !is_bool($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $value;
        }

        return $value;
    }

    /** @param array<string|int, mixed> $value */
    private function isTypedValue(array $value): bool
    {
        return array_key_exists('type', $value) && array_key_exists('data', $value);
    }

    /** @param array<string|int, mixed> $value */
    private function isTypedValueCollection(array $value): bool
    {
        if ($value === []) {
            return false;
        }

        foreach ($value as $item) {
            if (!$this->isTypedValue($this->toArray($item))) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string|int, mixed> */
    private function toArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return get_object_vars($value);
        }

        return [];
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
