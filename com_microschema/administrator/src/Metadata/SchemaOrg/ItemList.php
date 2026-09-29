<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class ItemList extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'ItemList';
    }

    public function getProperties(): array
    {
        return [
            $this->property('name'),
            $this->property('numberOfItems', ['Integer']),
            $this->property('itemListOrder', ['URL']),
            $this->property('itemListElement', ['ListItem'], required: true, multiple: true),
        ];
    }
}
