<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class ListItem extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'ListItem';
    }

    public function getProperties(): array
    {
        return [
            $this->property('position', ['Integer'], required: true),
            $this->property('name'),
            $this->property('url', ['URL']),
            $this->property('item', ['URL', 'NewsArticle', 'BlogPosting']),
        ];
    }
}
