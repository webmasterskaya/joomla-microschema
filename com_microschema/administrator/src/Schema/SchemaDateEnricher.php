<?php

namespace Joomla\Component\Microschema\Administrator\Schema;

use Joomla\Component\Microschema\Administrator\Metadata\DescriptorInterface;

final class SchemaDateEnricher
{
    /**
     * @param array<string, array<string, mixed>>              $schemas
     * @param array<string, class-string<DescriptorInterface>> $descriptors
     *
     * @return array<string, array<string, mixed>>
     */
    public function enrich(array $schemas, array $descriptors, string $timezone): array
    {
        if ($schemas === []) {
            return $schemas;
        }

        try {
            $targetTimezone = new \DateTimeZone($timezone !== '' ? $timezone : 'UTC');
        } catch (\Exception) {
            $targetTimezone = new \DateTimeZone('UTC');
        }

        $properties = $this->getDateProperties($descriptors);

        foreach ($schemas as $uid => $schema) {
            $schemas[$uid] = $this->enrichValue($schema, $properties, $targetTimezone);
        }

        return $schemas;
    }

    /**
     * @param array<string, class-string<DescriptorInterface>> $descriptors
     *
     * @return array<string, array<string, list<string>>>
     */
    private function getDateProperties(array $descriptors): array
    {
        $result = [];

        foreach ($descriptors as $descriptorClass) {
            $descriptor = new $descriptorClass();

            foreach ($descriptor->getProperties() as $property) {
                $dateTypes = array_values(array_intersect($property->types, ['Date', 'DateTime']));

                if ($dateTypes !== []) {
                    $result[$descriptor->getName()][$property->name] = $dateTypes;
                }
            }
        }

        return $result;
    }

    /** @param array<string, array<string, list<string>>> $properties */
    private function enrichValue(mixed $value, array $properties, \DateTimeZone $targetTimezone): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($this->normalizeTypes($value['@type'] ?? null) as $type) {
            foreach ($properties[$type] ?? [] as $name => $dateTypes) {
                if (!array_key_exists($name, $value)) {
                    continue;
                }

                $normalized = $this->normalizeDateValue($value[$name], $dateTypes, $targetTimezone);

                if ($normalized === null || $normalized === []) {
                    unset($value[$name]);
                } else {
                    $value[$name] = $normalized;
                }
            }
        }

        foreach ($value as $name => $item) {
            $value[$name] = $this->enrichValue($item, $properties, $targetTimezone);
        }

        return $value;
    }

    /** @param list<string> $dateTypes */
    private function normalizeDateValue(mixed $value, array $dateTypes, \DateTimeZone $targetTimezone): mixed
    {
        if (is_array($value) && array_is_list($value)) {
            $result = [];

            foreach ($value as $item) {
                $normalized = $this->normalizeDateValue($item, $dateTypes, $targetTimezone);

                if ($normalized !== null) {
                    $result[] = $normalized;
                }
            }

            return $result;
        }

        if (!is_string($value)) {
            return $value;
        }

        $value = trim($value);

        if ($value === '' || preg_match('/^0{4}-0{2}-0{2}/', $value) === 1) {
            return null;
        }

        $dateOnly = preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
        $type = $dateOnly && in_array('Date', $dateTypes, true) ? 'Date' : 'DateTime';

        if (!in_array($type, $dateTypes, true)) {
            $type = $dateTypes[0] ?? 'DateTime';
        }

        try {
            if ($type === 'Date') {
                return (new \DateTimeImmutable($value, new \DateTimeZone('UTC')))->format('Y-m-d');
            }

            $hasTimezone = preg_match('/(?:Z|[+-]\d{2}:?\d{2})$/i', $value) === 1;
            $date = $hasTimezone
                ? new \DateTimeImmutable($value)
                : new \DateTimeImmutable($value, new \DateTimeZone('UTC'));

            return $date->setTimezone($targetTimezone)->format('Y-m-d\TH:i:sP');
        } catch (\Exception) {
            return null;
        }
    }

    /** @return list<string> */
    private function normalizeTypes(mixed $types): array
    {
        if (is_string($types)) {
            $types = [$types];
        }

        if (!is_array($types)) {
            return [];
        }

        return array_values(array_filter($types, static fn (mixed $type): bool => is_string($type) && $type !== ''));
    }
}
