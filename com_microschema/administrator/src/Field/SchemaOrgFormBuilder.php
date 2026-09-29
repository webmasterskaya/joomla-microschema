<?php

namespace Joomla\Component\Microschema\Administrator\Field;

use Joomla\CMS\Form\Field\SubformField;
use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormField;
use Joomla\Component\Microschema\Administrator\Metadata\DescriptorInterface;
use Joomla\Component\Microschema\Administrator\Metadata\PropertyDefinition;

final class SchemaOrgFormBuilder
{
    public const MAX_DEPTH = 4;

    /** @param array<string, class-string<DescriptorInterface>> $schemas */
    public function __construct(
        private readonly Form $form,
        private readonly array $schemas,
        private readonly string $reloadTask,
    ) {
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*\.[A-Za-z][A-Za-z0-9_]*$/D', $reloadTask)) {
            throw new \InvalidArgumentException('The form reload task is invalid.');
        }
    }

    /** @param list<string> $path */
    public function renderDescriptor(
        DescriptorInterface $descriptor,
        mixed $value,
        string $group,
        int $depth,
        array $path,
    ): string {
        $html = [];

        foreach ($descriptor->getProperties() as $property) {
            $field = $this->createPropertyField(
                $property,
                $this->getValue($value, $property->name),
                $group,
                $depth,
                $path,
            );
            $html[] = $field->renderField();
        }

        return implode("\n", $html);
    }

    /** @param list<string> $path */
    private function createPropertyField(
        PropertyDefinition $property,
        mixed $value,
        string $group,
        int $depth,
        array $path,
    ): FormField {
        if (!$property->multiple && count($property->types) === 1 && !$this->isObjectType($property->types[0])) {
            return $this->createTextField($property, $value, $group);
        }

        $xml = new \SimpleXMLElement('<field type="subform" validate="Subform" />');
        $this->configureField($xml, $property);
        $xml->addAttribute('multiple', $property->multiple ? 'true' : 'false');

        if ($property->multiple) {
            $xml->addAttribute('layout', 'joomla.form.field.subform.repeatable-table');
        }

        if ($property->required && $property->multiple) {
            $xml->addAttribute('min', '1');
        }

        $form = $xml->addChild('form');
        $fieldset = $form->addChild('fieldset');
        $fieldset->addAttribute('name', 'value');
        $fieldset->addAttribute('addfieldprefix', 'Joomla\Component\Microschema\Administrator\Field');

        $typeField = $fieldset->addChild('field');
        $typeField->addAttribute('name', 'type');
        $typeField->addAttribute('label', 'COM_MICROSCHEMA_FIELD_SCHEMA_PROPERTY_TYPE_LABEL');

        if (count($property->types) > 1) {
            $typeField->addAttribute('type', 'list');
            $typeField->addAttribute('required', 'true');
            $typeField->addAttribute(
                'onchange',
                sprintf(
                    "document.body.appendChild(document.createElement('joomla-core-loader')); Joomla.submitform('%s', this.form)",
                    $this->reloadTask,
                ),
            );

            foreach ($property->types as $type) {
                $option = $typeField->addChild('option', $type);
                $option->addAttribute('value', $type);
            }
        } else {
            $typeField->addAttribute('type', 'hidden');
            $typeField->addAttribute('default', $property->types[0]);
        }

        $valueField = $fieldset->addChild('field');
        $valueField->addAttribute('name', 'data');
        $valueField->addAttribute('type', 'schemaorgvalue');
        $valueField->addAttribute('label', $property->name);
        $valueField->addAttribute('hiddenLabel', 'true');
        $valueField->addAttribute('types', implode(',', $property->types));
        $valueField->addAttribute('depth', (string) $depth);
        $valueField->addAttribute('path', implode(',', $path));
        $valueField->addAttribute('reloadtask', $this->reloadTask);

        if ($property->required) {
            $valueField->addAttribute('propertyrequired', 'true');
        }

        $field = new SubformField($this->form);

        if (!$field->setup($xml, $value, $group)) {
            throw new \RuntimeException(sprintf('Unable to create the "%s" Schema.org property field.', $property->name));
        }

        return $field;
    }

    private function createTextField(PropertyDefinition $property, mixed $value, string $group): TextField
    {
        $xml = new \SimpleXMLElement('<field type="text" />');
        $this->configureField($xml, $property);
        $field = new TextField($this->form);

        if (!$field->setup($xml, $value, $group)) {
            throw new \RuntimeException(sprintf('Unable to create the "%s" Schema.org property field.', $property->name));
        }

        return $field;
    }

    private function configureField(\SimpleXMLElement $xml, PropertyDefinition $property): void
    {
        $xml->addAttribute('name', $property->name);
        $xml->addAttribute('label', $property->name);
        $xml->addAttribute('description', implode(', ', $property->types));

        if ($property->required) {
            $xml->addAttribute('required', 'true');
        }
    }

    private function isObjectType(string $type): bool
    {
        return isset($this->schemas[$type]);
    }

    private function getValue(mixed $value, string $name): mixed
    {
        if (is_array($value) && array_key_exists($name, $value)) {
            return $value[$name];
        }

        if (is_object($value) && property_exists($value, $name)) {
            return $value->{$name};
        }

        return null;
    }
}
