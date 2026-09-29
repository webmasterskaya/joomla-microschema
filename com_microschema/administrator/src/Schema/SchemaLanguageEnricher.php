<?php

namespace Joomla\Component\Microschema\Administrator\Schema;

use Joomla\Component\Microschema\Administrator\Metadata\DescriptorInterface;

final class SchemaLanguageEnricher
{
    /**
     * @param array<string, array<string, mixed>>              $schemas
     * @param array<string, class-string<DescriptorInterface>> $descriptors
     *
     * @return array<string, array<string, mixed>>
     */
    public function enrich(array $schemas, array $descriptors, string $languageTag): array
    {
        $languageTag = trim($languageTag);

        if ($schemas === [] || $languageTag === '') {
            return $schemas;
        }

        $languageAwareTypes = $this->getLanguageAwareTypes($descriptors);

        if ($languageAwareTypes === []) {
            return $schemas;
        }

        foreach ($schemas as $uid => $schema) {
            $schemas[$uid] = $this->enrichValue($schema, $languageAwareTypes, $languageTag);
        }

        return $schemas;
    }

    /**
     * @param array<string, class-string<DescriptorInterface>> $descriptors
     *
     * @return array<string, true>
     */
    private function getLanguageAwareTypes(array $descriptors): array
    {
        $types = [];

        foreach ($descriptors as $descriptorClass) {
            $descriptor = new $descriptorClass();

            foreach ($descriptor->getProperties() as $property) {
                if ($property->name === 'inLanguage' && $property->automatic) {
                    $types[$descriptor->getName()] = true;
                    break;
                }
            }
        }

        return $types;
    }

    /**
     * @param array<string, true> $languageAwareTypes
     */
    private function enrichValue(mixed $value, array $languageAwareTypes, string $languageTag): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($this->normalizeTypes($value['@type'] ?? null) as $type) {
            if (isset($languageAwareTypes[$type])) {
                $value['inLanguage'] = $languageTag;
                break;
            }
        }

        foreach ($value as $name => $item) {
            $value[$name] = $this->enrichValue($item, $languageAwareTypes, $languageTag);
        }

        return $value;
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

        $normalized = [];

        foreach ($types as $type) {
            if (is_string($type) && $type !== '') {
                $normalized[] = $type;
            }
        }

        return $normalized;
    }
}
