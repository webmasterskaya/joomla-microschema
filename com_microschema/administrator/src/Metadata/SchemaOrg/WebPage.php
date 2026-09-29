<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

class WebPage extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'WebPage';
    }

    public function getProperties(): array
    {
        return [
            $this->property('name', required: true),
            $this->property('url', ['URL'], required: true),
            $this->property('description'),
            $this->property('isPartOf', ['WebSite']),
            $this->property('about', ['Organization']),
            $this->property('breadcrumb', ['BreadcrumbList']),
            $this->property('mainEntity', ['ItemList', 'NewsArticle', 'BlogPosting']),
            $this->property('inLanguage', automatic: true),
        ];
    }
}
