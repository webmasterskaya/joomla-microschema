<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class Service extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'Service';
    }

    public function getProperties(): array
    {
        return [
            $this->property('name', required: true), $this->property('description'),
            $this->property('serviceType'), $this->property('provider', ['Person', 'Organization']),
            $this->property('areaServed', ['Place', 'AdministrativeArea', 'string'], multiple: true),
            $this->property('offers', ['Offer', 'AggregateOffer'], multiple: true),
        ];
    }
}
