<?php

/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\MicroschemaYootheme\Builder;

defined('_JEXEC') || exit;

use Joomla\Component\Microschema\Administrator\Schema\SchemaDataBuilder;

final class SchemaElementPayloadBuilder
{
    /**
     * @param list<string> $registeredTypes
     *
     * @return array<string, mixed>
     */
    public function build(string $schemaType, mixed $properties, array $registeredTypes): array
    {
        $schemaType = trim($schemaType);

        if ($schemaType === '' || !in_array($schemaType, $registeredTypes, true)) {
            return [];
        }

        $properties = $this->decodeProperties($properties);
        unset($properties['@context'], $properties['@type']);

        $schema = (new SchemaDataBuilder($registeredTypes))->build($schemaType, $properties);

        return count($schema) > 1 ? $schema : [];
    }

    /** @return array<string, mixed> */
    private function decodeProperties(mixed $properties): array
    {
        if (is_string($properties)) {
            if (trim($properties) === '') {
                return [];
            }

            try {
                $properties = json_decode($properties, false, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new \InvalidArgumentException('Schema properties must contain valid JSON.', 0, $exception);
            }
        }

        $isObject = is_object($properties);

        if ($isObject) {
            $properties = get_object_vars($properties);
        }

        if (!is_array($properties) || (!$isObject && array_is_list($properties))) {
            throw new \InvalidArgumentException('Schema properties must be a JSON object.');
        }

        return $properties;
    }
}
