<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class AggregateOffer extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'AggregateOffer';
    }

    public function getProperties(): array
    {
        return [
            $this->property('lowPrice', ['Number'], required: true),
            $this->property('highPrice', ['Number']),
            $this->property('priceCurrency', required: true),
            $this->property('offerCount', ['Integer']),
            $this->property('offers', ['Offer'], multiple: true),
        ];
    }
}
