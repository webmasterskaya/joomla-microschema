<?php

namespace Joomla\Component\Microschema\Administrator\Schema;

final class SchemaDataBuilder
{
    private const REFERENCE_TYPE = '@id';

    /** @var array<string, true> */
    private array $objectTypes;

    /** @param list<string> $objectTypes */
    public function __construct(array $objectTypes)
    {
        $this->objectTypes = array_fill_keys($objectTypes, true);
    }

    /** @return array<string, mixed> */
    public function build(string $schemaType, mixed $properties): array
    {
        $schemaType = trim($schemaType);

        if ($schemaType === '') {
            return [];
        }

        $properties = $this->toArray($properties);
        $schema = ['@type' => $schemaType];

        foreach ($properties as $name => $value) {
            $value = $this->normalize($value);

            if (!$this->isEmpty($value)) {
                $schema[(string) $name] = $value;
            }
        }

        return $schema;
    }

    private function normalize(mixed $value): mixed
    {
        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        if (!is_array($value)) {
            return $value;
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
        $data = $this->normalize($value['data'] ?? null);

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
