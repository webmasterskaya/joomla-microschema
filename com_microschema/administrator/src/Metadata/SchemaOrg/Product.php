<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class Product extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'Product';
    }

    public function getProperties(): array
    {
        return [
            $this->property('name', required: true), $this->property('description'),
            $this->property('image', ['URL', 'ImageObject'], multiple: true),
            $this->property('sku'), $this->property('gtin'),
            $this->property('brand', ['Brand', 'Organization']),
            $this->property('offers', ['Demand', 'Offer'], required: true, multiple: true),
            $this->property('aggregateRating', ['AggregateRating']),
            $this->property('review', ['Review'], multiple: true),
        ];
    }
}
