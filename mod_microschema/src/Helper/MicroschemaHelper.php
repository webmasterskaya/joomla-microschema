<?php

namespace Joomla\Module\Microschema\Site\Helper;

defined('_JEXEC') || exit;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Component\Microschema\Administrator\Extension\MicroschemaComponent;
use Joomla\Component\Microschema\Administrator\Schema\SchemaDataBuilder;
use Joomla\Registry\Registry;

final class MicroschemaHelper
{
    public function registerSchema(Registry $params, \stdClass $module, CMSApplicationInterface $application): void
    {
        $schemaType = trim((string) $params->get('schema_type', ''));

        if ($schemaType === '') {
            return;
        }

        $component = $application->bootComponent('com_microschema');

        if (!$component instanceof MicroschemaComponent) {
            return;
        }

        $builder = new SchemaDataBuilder($component->getMetadataRegistry()->getSchemaOrg());
        $schema = $builder->build($schemaType, $params->get('schema_properties', []));

        if ($schema === []) {
            return;
        }

        $component->getSchemaCollector()->addSchema(
            'module.microschema.'.(int) $module->id,
            $schema,
        );
    }
}
