<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class FAQPage extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'FAQPage';
    }

    public function getProperties(): array
    {
        return [$this->property('mainEntity', ['Question'], required: true, multiple: true)];
    }
}
