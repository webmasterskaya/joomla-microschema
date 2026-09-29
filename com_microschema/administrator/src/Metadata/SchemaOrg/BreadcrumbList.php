<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class BreadcrumbList extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'BreadcrumbList';
    }

    public function getProperties(): array
    {
        return [$this->property('itemListElement', ['ListItem'], required: true, multiple: true)];
    }
}
