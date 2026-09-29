<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class Event extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'Event';
    }

    public function getProperties(): array
    {
        return [
            $this->property('name', required: true), $this->property('description'),
            $this->property('startDate', ['Date', 'DateTime'], required: true),
            $this->property('endDate', ['Date', 'DateTime']),
            $this->property('eventStatus', ['URL']), $this->property('eventAttendanceMode', ['URL']),
            $this->property('location', ['Place', 'PostalAddress', 'VirtualLocation'], required: true),
            $this->property('image', ['URL', 'ImageObject'], multiple: true),
            $this->property('organizer', ['Person', 'Organization']),
            $this->property('performer', ['Person', 'Organization'], multiple: true),
            $this->property('offers', ['Offer'], multiple: true),
        ];
    }
}
