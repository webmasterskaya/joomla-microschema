<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class AggregateRating extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'AggregateRating';
    }

    public function getProperties(): array
    {
        return [
            $this->property('ratingValue', ['Number'], required: true),
            $this->property('ratingCount', ['Integer']),
            $this->property('reviewCount', ['Integer']),
            $this->property('bestRating', ['Number']),
            $this->property('worstRating', ['Number']),
        ];
    }
}
