<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class ImageObject extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'ImageObject';
    }

    public function getProperties(): array
    {
        return [
            $this->property('contentUrl', ['URL'], required: true),
            $this->property('url', ['URL']),
            $this->property('caption'),
            $this->property('width', ['Integer']),
            $this->property('height', ['Integer']),
            $this->property('encodingFormat'),
            $this->property('representativeOfPage', ['Boolean']),
        ];
    }
}
