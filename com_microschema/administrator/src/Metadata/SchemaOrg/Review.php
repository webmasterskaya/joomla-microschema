<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class Review extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'Review';
    }

    public function getProperties(): array
    {
        return [
            $this->property('itemReviewed', ['Thing'], required: true),
            $this->property('author', ['Person', 'Organization'], required: true),
            $this->property('reviewRating', ['Rating'], required: true),
            $this->property('reviewBody'), $this->property('datePublished', ['Date']),
            $this->property('publisher', ['Organization']),
        ];
    }
}
