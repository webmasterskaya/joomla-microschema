<?php

/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\MicroschemaYootheme\Builder;

defined('_JEXEC') || exit;

use Joomla\Component\Microschema\Administrator\Schema\SchemaDataBuilder;

final class SchemaListPayloadBuilder
{
    public const ITEM_TYPES = ['URL', 'BlogPosting', 'NewsArticle'];

    private const LIST_TYPES = ['BreadcrumbList', 'ItemList'];

    /**
     * @param array<string, mixed> $props
     * @param list<object|array<string, mixed>> $children
     * @param list<string> $registeredTypes
     * @param array<string, class-string<\Joomla\Component\Microschema\Administrator\Metadata\DescriptorInterface>> $schemas
     *
     * @return array<string, mixed>
     */
    public function build(
        string $schemaType,
        array $props,
        array $children,
        array $registeredTypes,
        array $schemas = [],
    ): array
    {
        if (!in_array($schemaType, self::LIST_TYPES, true)
            || !in_array($schemaType, $registeredTypes, true)
            || !in_array('ListItem', $registeredTypes, true)) {
            return [];
        }

        $items = [];

        foreach ($children as $child) {
            $childProps = $this->getChildProps($child);

            if (in_array($childProps['status'] ?? null, [false, 0, '0'], true)) {
                continue;
            }

            $item = $this->buildItem($childProps, count($items) + 1, $schemas);

            if ($item !== []) {
                $items[] = ['type' => 'ListItem', 'data' => $item];
            }
        }

        if ($items === []) {
            return [];
        }

        $properties = ['itemListElement' => $items];

        if ($schemaType === 'ItemList') {
            $properties = [
                'name' => $props['list_name'] ?? null,
                'numberOfItems' => count($items),
                'itemListOrder' => $props['item_list_order'] ?? null,
            ] + $properties;
        }

        $schema = (new SchemaDataBuilder($registeredTypes))->build($schemaType, $properties);

        return count($schema) > 1 ? $schema : [];
    }

    /**
     * @param array<string, mixed> $props
     *
     * @return array<string, mixed>
     */
    private function buildItem(array $props, int $position, array $schemas): array
    {
        $data = [];

        foreach (['title' => 'name', 'url' => 'url'] as $field => $property) {
            $value = $props[$field] ?? null;

            if (!$this->isEmpty($value)) {
                $data[$property] = $value;
            }
        }

        $item = $this->buildNestedItem($props, $schemas);

        if (!$this->isEmpty($item)) {
            $data['item'] = $item;
        }

        return $data === [] ? [] : ['position' => $position] + $data;
    }

    /**
     * @param array<string, mixed> $props
     * @param array<string, class-string<\Joomla\Component\Microschema\Administrator\Metadata\DescriptorInterface>> $schemas
     */
    private function buildNestedItem(array $props, array $schemas): mixed
    {
        $itemType = trim((string) ($props['item_type'] ?? ''));

        if ($itemType === '') {
            return $props['item'] ?? null;
        }

        if ($itemType === 'URL') {
            return $props['item_url'] ?? ($props['item'] ?? null);
        }

        if (!in_array($itemType, self::ITEM_TYPES, true) || !isset($schemas[$itemType])) {
            return null;
        }

        $itemSchemas = array_intersect_key($schemas, array_fill_keys(self::ITEM_TYPES, true));
        $fieldBuilder = new SchemaElementFieldBuilder(
            $itemSchemas,
            [],
            'item_type',
            'item_schema',
        );
        $properties = $fieldBuilder->extractProperties($itemType, $props);
        $id = trim((string) ($props['item_id'] ?? ''));

        if ($id === '' && $properties === []) {
            return null;
        }

        return ['@type' => $itemType]
            + ($id === '' ? [] : ['@id' => $id])
            + $properties;
    }

    /** @return array<string, mixed> */
    private function getChildProps(object|array $child): array
    {
        if (is_array($child)) {
            $props = $child['props'] ?? [];
        } else {
            $props = $child->props ?? [];
        }

        return is_array($props) ? $props : [];
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
