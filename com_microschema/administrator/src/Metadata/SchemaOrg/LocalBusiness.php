<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

final class LocalBusiness extends Organization
{
    public function getName(): string
    {
        return 'LocalBusiness';
    }

    public function getProperties(): array
    {
        return [...parent::getProperties(),
            $this->property('priceRange'),
            $this->property('geo', ['GeoCoordinates']),
            $this->property('openingHoursSpecification', ['OpeningHoursSpecification'], multiple: true),
        ];
    }
}
