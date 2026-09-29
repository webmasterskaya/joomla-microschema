<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class Demand extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'Demand';
    }

    public function getProperties(): array
    {
        return [
            $this->property('name'),
            $this->property('description'),
            $this->property('availabilityStarts', ['DateTime']),
            $this->property('availabilityEnds', ['DateTime']),
            $this->property('eligibleRegion'),
            $this->property('url', ['URL']),
        ];
    }
}
