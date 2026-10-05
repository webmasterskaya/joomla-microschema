<?php

/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\MicroschemaYootheme\Builder;

defined('_JEXEC') || exit;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Component\Microschema\Administrator\Extension\MicroschemaComponent;

final readonly class SchemaElementRegistrar
{
    public function __construct(
        private CMSApplicationInterface $application,
        private SchemaElementPayloadBuilder $payloadBuilder,
    ) {
    }

    /**
     * @param list<string> $excludedTypes
     *
     * @return array<string, string>
     */
    public function getTypeOptions(array $excludedTypes = []): array
    {
        $component = $this->getComponent();

        if ($component === null) {
            return [];
        }

        $options = [];

        $schemas = array_diff_key(
            $component->getMetadataRegistry()->getSelectableSchemaOrg(),
            array_fill_keys($excludedTypes, true),
        );

        foreach (array_keys($schemas) as $type) {
            $options[$type] = $type;
        }

        return $options;
    }

    /**
     * @param list<string> $excludedTypes
     *
     * @return array{
     *     fields: array<string, array<string, mixed>>,
     *     names: list<string>,
     *     groups: list<array{label: string, type: string, divider: bool, fields: list<string>, show: string}>
     * }
     */
    public function getFieldConfiguration(array $excludedTypes = []): array
    {
        $component = $this->getComponent();

        if ($component === null) {
            return ['fields' => [], 'names' => [], 'groups' => []];
        }

        return (new SchemaElementFieldBuilder(
            $component->getMetadataRegistry()->getSelectableSchemaOrg(),
            $excludedTypes,
        ))->build();
    }

    public function register(object $node): bool
    {
        $component = $this->getComponent();

        if ($component === null) {
            return false;
        }

        $props = is_array($node->props ?? null) ? $node->props : [];
        $schemaType = (string) ($props['schema_type'] ?? '');
        $registeredTypes = array_keys($component->getMetadataRegistry()->getSchemaOrg());
        $fieldBuilder = new SchemaElementFieldBuilder(
            $component->getMetadataRegistry()->getSelectableSchemaOrg(),
        );
        $properties = $fieldBuilder->hasSubmittedFields($schemaType, $props)
            ? $fieldBuilder->extractProperties($schemaType, $props)
            : ($props['schema_properties'] ?? '');

        try {
            $schema = $this->payloadBuilder->build(
                $schemaType,
                $properties,
                $registeredTypes,
            );
        } catch (\InvalidArgumentException) {
            return false;
        }

        if ($schema === []) {
            return false;
        }

        $component->getSchemaCollector()->addSchema(
            'yootheme.microschema.'.spl_object_id($node),
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
