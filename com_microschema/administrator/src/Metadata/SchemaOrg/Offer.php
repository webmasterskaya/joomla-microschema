<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class Offer extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'Offer';
    }

    public function getProperties(): array
    {
        return [
            $this->property('price', ['Number'], required: true),
            $this->property('priceCurrency', required: true),
            $this->property('availability', ['URL']),
            $this->property('url', ['URL']),
            $this->property('priceValidUntil', ['Date']),
            $this->property('itemCondition', ['URL']),
            $this->property('seller', ['Organization', 'Person']),
        ];
    }
}
