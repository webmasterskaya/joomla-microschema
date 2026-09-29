<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

class Organization extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'Organization';
    }

    public function getProperties(): array
    {
        return [
            $this->property('name', required: true), $this->property('legalName'),
            $this->property('url', ['URL']),
            $this->property('logo', ['URL', 'ImageObject']),
            $this->property('description'), $this->property('email'), $this->property('telephone'),
            $this->property('address', ['PostalAddress']),
            $this->property('contactPoint', ['ContactPoint'], multiple: true),
            $this->property('sameAs', ['URL'], multiple: true),
        ];
    }
}
