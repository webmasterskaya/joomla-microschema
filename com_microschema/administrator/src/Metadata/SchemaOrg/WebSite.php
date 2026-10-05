<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class WebSite extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'WebSite';
    }

    public function getProperties(): array
    {
        return [
            $this->property('name', required: true),
            $this->property('url', ['URL'], required: true),
            $this->property('publisher', ['Organization', '@id']),
            $this->property('inLanguage', automatic: true),
        ];
    }
}
