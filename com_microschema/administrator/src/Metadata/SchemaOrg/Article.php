<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

class Article extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'Article';
    }

    public function getProperties(): array
    {
        return [
            $this->property('headline', required: true),
            $this->property('url', ['URL']),
            $this->property('description'),
            $this->property('image', ['URL', 'ImageObject', '@id'], multiple: true),
            $this->property('author', ['Person', 'Organization', '@id'], required: true, multiple: true),
            $this->property('publisher', ['Organization', '@id']),
            $this->property('datePublished', ['Date', 'DateTime'], required: true),
            $this->property('dateModified', ['Date', 'DateTime']),
            $this->property('mainEntityOfPage', ['URL', 'WebPage', '@id']),
            $this->property('articleSection'),
            $this->property('articleBody'),
            $this->property('keywords', ['string'], multiple: true),
            $this->property('inLanguage', automatic: true),
            $this->property('isAccessibleForFree', ['Boolean']),
        ];
    }
}
