<?php

/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\MicroschemaYootheme\Builder;

defined('_JEXEC') || exit;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Microschema\Administrator\Extension\MicroschemaComponent;

final readonly class SchemaListRegistrar
{
    private SchemaListIdentity $identity;

    public function __construct(
        private CMSApplicationInterface $application,
        private SchemaListPayloadBuilder $payloadBuilder,
    ) {
        $this->identity = new SchemaListIdentity();
    }

    /** @return array<string, string> */
    public function getItemTypeOptions(): array
    {
        $component = $this->getComponent();

        if ($component === null) {
            return [];
        }

        $registeredTypes = $component->getMetadataRegistry()->getSchemaOrg();
        $options = [];

        foreach (SchemaListPayloadBuilder::ITEM_TYPES as $type) {
            if ($type === 'URL' || isset($registeredTypes[$type])) {
                $options[$type] = $type;
            }
        }

        return $options;
    }

    /**
     * @return array{
     *     fields: array<string, array<string, mixed>>,
     *     names: list<string>,
     *     groups: list<array{label: string, type: string, divider: bool, fields: list<string>, show: string}>
     * }
     */
    public function getItemFieldConfiguration(): array
    {
        $component = $this->getComponent();

        if ($component === null) {
            return ['fields' => [], 'names' => [], 'groups' => []];
        }

        $schemas = array_intersect_key(
            $component->getMetadataRegistry()->getSchemaOrg(),
            array_fill_keys(SchemaListPayloadBuilder::ITEM_TYPES, true),
        );

        return (new SchemaElementFieldBuilder(
            $schemas,
            [],
            'item_type',
            'item_schema',
        ))->build();
    }

    public function register(object $node): bool
    {
        $component = $this->getComponent();

        if ($component === null) {
            return false;
        }

        $props = is_array($node->props ?? null) ? $node->props : [];
        $children = is_array($node->children ?? null) ? $node->children : [];
        $schemas = $component->getMetadataRegistry()->getSchemaOrg();
        $schema = $this->payloadBuilder->build(
            (string) ($props['schema_type'] ?? ''),
            $props,
            $children,
            array_keys($schemas),
            $schemas,
        );

        if ($schema === []) {
            return false;
        }

        if (($schema['@type'] ?? '') === 'ItemList') {
            $schema['@id'] = $this->identity->resolve(
                $node,
                (string) ($props['schema_id'] ?? ''),
                Uri::getInstance()->toString(['scheme', 'host', 'port', 'path', 'query']),
            );
        }

        $component->getSchemaCollector()->addSchema(
            'yootheme.microschema.list.'.($schema['@id'] ?? spl_object_id($node)),
            $schema,
            (int) ($props['priority'] ?? 0),
        );

        return true;
    }

    private function getComponent(): ?MicroschemaComponent
    {
        $component = $this->application->bootComponent('com_microschema');

        return $component instanceof MicroschemaComponent ? $component : null;
    }
}
