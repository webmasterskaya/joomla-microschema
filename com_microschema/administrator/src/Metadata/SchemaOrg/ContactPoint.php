<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class ContactPoint extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'ContactPoint';
    }

    public function getProperties(): array
    {
        return [
            $this->property('contactType'),
            $this->property('telephone'),
            $this->property('email'),
            $this->property('faxNumber'),
            $this->property('areaServed', ['AdministrativeArea', 'GeoShape', 'Place', 'string'], multiple: true),
            $this->property('availableLanguage', ['Language', 'string'], multiple: true),
            $this->property('contactOption', ['ContactPointOption'], multiple: true),
            $this->property('hoursAvailable', ['OpeningHoursSpecification'], multiple: true),
            $this->property('productSupported', ['Product', 'string'], multiple: true),
        ];
    }
}
