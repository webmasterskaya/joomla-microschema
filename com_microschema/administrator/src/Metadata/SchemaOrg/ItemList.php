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
            $this->property('itemListOrder', ['ItemListOrderType'], options: [
                'https://schema.org/ItemListUnordered' => 'COM_MICROSCHEMA_SCHEMA_EDITOR_ITEM_LIST_ORDER_UNORDERED',
                'https://schema.org/ItemListOrderAscending' => 'COM_MICROSCHEMA_SCHEMA_EDITOR_ITEM_LIST_ORDER_ASCENDING',
                'https://schema.org/ItemListOrderDescending' => 'COM_MICROSCHEMA_SCHEMA_EDITOR_ITEM_LIST_ORDER_DESCENDING',
            ]),
            $this->property('itemListElement', ['ListItem'], required: true, multiple: true),
        ];
    }
}
