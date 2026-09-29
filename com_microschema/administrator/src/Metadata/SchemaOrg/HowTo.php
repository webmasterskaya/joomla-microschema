<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class HowTo extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'HowTo';
    }

    public function getProperties(): array
    {
        return [
            $this->property('name', required: true), $this->property('description'),
            $this->property('image', ['URL', 'ImageObject'], multiple: true),
            $this->property('totalTime', ['Duration']), $this->property('estimatedCost', ['MonetaryAmount', 'string']),
            $this->property('supply', ['HowToSupply', 'string'], multiple: true),
            $this->property('tool', ['HowToTool', 'string'], multiple: true),
            $this->property('step', ['HowToStep', 'HowToSection', 'string'], required: true, multiple: true),
        ];
    }
}
