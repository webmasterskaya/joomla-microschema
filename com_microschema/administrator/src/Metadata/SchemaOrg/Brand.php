<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class Brand extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'Brand';
    }

    public function getProperties(): array
    {
        return [
            $this->property('name', required: true),
            $this->property('url', ['URL']),
            $this->property('logo', ['URL', 'ImageObject']),
        ];
    }
}
