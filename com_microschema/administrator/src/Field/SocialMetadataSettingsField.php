<?php

namespace Joomla\Component\Microschema\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\RadioField;
use Joomla\CMS\Form\FormField;
use Joomla\Component\Microschema\Administrator\Extension\MicroschemaComponent;

final class SocialMetadataSettingsField extends FormField
{
    protected $type = 'SocialMetadataSettings';

    protected function getInput(): string
    {
        $component = Factory::getApplication()->bootComponent('com_microschema');

        if (!$component instanceof MicroschemaComponent) {
            throw new \RuntimeException('The com_microschema component is not available.');
        }

        $html = [];

        foreach (array_keys($component->getMetadataRegistry()->getSocials()) as $name) {
            $xml = new \SimpleXMLElement(
                '<field type="radio" layout="joomla.form.field.radio.switcher">'
                .'<option value="0">JNO</option>'
                .'<option value="1">JYES</option>'
                .'</field>'
            );
            $xml->addAttribute('name', $name);
            $xml->addAttribute('label', $name);

            $field = new RadioField($this->form);
            $group = trim($this->group.'.'.$this->fieldname, '.');

            if (!$field->setup($xml, (int) $this->isEnabled($name), $group)) {
                throw new \RuntimeException(sprintf('Unable to create the "%s" social field.', $name));
            }

            $html[] = $field->renderField();
        }

        return implode("\n", $html);
    }

    private function isEnabled(string $name): bool
    {
        if (is_array($this->value) && array_key_exists($name, $this->value)) {
            return (bool) $this->value[$name];
        }

        if (is_object($this->value) && property_exists($this->value, $name)) {
            return (bool) $this->value->{$name};
        }

        return false;
    }
}
