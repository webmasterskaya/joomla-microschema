<?php

namespace Joomla\Component\Microschema\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Component\Microschema\Administrator\Extension\MicroschemaComponent;

final class DataSourceField extends ListField
{
    protected $type = 'DataSource';

    protected function getOptions(): array
    {
        $options = parent::getOptions();
        $component = Factory::getApplication()->bootComponent('com_microschema');

        if (!$component instanceof MicroschemaComponent) {
            throw new \RuntimeException('The com_microschema component is not available.');
        }

        $context = trim((string) ($this->element['context'] ?? ''));

        foreach ($component->getDataSourceRegistry()->forContext($context) as $source) {
            $options[] = HTMLHelper::_('select.option', $source->getName(), $source->getLabel());
        }

        return $options;
    }
}
