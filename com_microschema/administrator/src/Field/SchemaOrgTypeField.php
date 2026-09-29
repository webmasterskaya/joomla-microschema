<?php

namespace Joomla\Component\Microschema\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Component\Microschema\Administrator\Extension\MicroschemaComponent;

final class SchemaOrgTypeField extends ListField
{
    protected $type = 'SchemaOrgType';

    protected function getOptions(): array
    {
        $component = Factory::getApplication()->bootComponent('com_microschema');

        if (!$component instanceof MicroschemaComponent) {
            throw new \RuntimeException('The com_microschema component is not available.');
        }

        $options = parent::getOptions();

        foreach (array_keys($component->getMetadataRegistry()->getSelectableSchemaOrg()) as $name) {
            $options[] = HTMLHelper::_('select.option', $name, $name);
        }

        return $options;
    }
}
