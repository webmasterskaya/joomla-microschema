<?php

namespace Joomla\Component\Microschema\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Form\FormField;
use Joomla\Component\Microschema\Administrator\Extension\MicroschemaComponent;

final class SchemaOrgValueField extends FormField
{
    protected $type = 'SchemaOrgValue';

    protected function getInput(): string
    {
        $component = Factory::getApplication()->bootComponent('com_microschema');

        if (!$component instanceof MicroschemaComponent) {
            throw new \RuntimeException('The com_microschema component is not available.');
        }

        $types = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) ($this->element['types'] ?? '')),
        )));
        $type = trim((string) $this->form->getValue('type', $this->group));

        if ($type === '') {
            $type = trim((string) $this->form->getValue('type'));
        }

        if ($type === '' && count($types) === 1) {
            $type = $types[0];
        }

        if ($type === '' || !in_array($type, $types, true)) {
            return '';
        }

        $schemas = $component->getMetadataRegistry()->getSchemaOrg();

        if (!isset($schemas[$type])) {
            return $this->renderScalar();
        }

        $depth = (int) ($this->element['depth'] ?? 0);
        $path = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) ($this->element['path'] ?? '')),
        )));

        if ($depth >= SchemaOrgFormBuilder::MAX_DEPTH || in_array($type, $path, true)) {
            return $this->renderScalar();
        }

        $descriptorClass = $schemas[$type];
        $descriptor = new $descriptorClass();
        $group = trim($this->group.'.'.$this->fieldname, '.');
        $reloadTask = trim((string) ($this->element['reloadtask'] ?? ''));
        $builder = new SchemaOrgFormBuilder($this->form, $schemas, $reloadTask);

        return $builder->renderDescriptor($descriptor, $this->value, $group, $depth + 1, [...$path, $type]);
    }

    private function renderScalar(): string
    {
        $xml = new \SimpleXMLElement('<field type="text" />');
        $xml->addAttribute('name', $this->fieldname);
        $xml->addAttribute('label', 'COM_MICROSCHEMA_FIELD_SCHEMA_PROPERTY_VALUE_LABEL');

        if ((string) ($this->element['propertyrequired'] ?? 'false') === 'true') {
            $xml->addAttribute('required', 'true');
        }

        $field = new TextField($this->form);
        $value = is_scalar($this->value) || $this->value instanceof \Stringable
            ? (string) $this->value
            : '';

        if (!$field->setup($xml, $value, $this->group)) {
            throw new \RuntimeException('Unable to create the Schema.org scalar value field.');
        }

        return (string) $field->__get('input');
    }
}
