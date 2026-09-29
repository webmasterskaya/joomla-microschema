<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class Person extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'Person';
    }

    public function getProperties(): array
    {
        return [
            $this->property('name', required: true), $this->property('url', ['URL']),
            $this->property('image', ['URL', 'ImageObject']), $this->property('jobTitle'),
            $this->property('worksFor', ['Organization']),
            $this->property('contactPoint', ['ContactPoint'], multiple: true),
            $this->property('sameAs', ['URL'], multiple: true),
        ];
    }
}
