<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class HowToStep extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'HowToStep';
    }

    public function getProperties(): array
    {
        return [
            $this->property('name'),
            $this->property('text', required: true),
            $this->property('url', ['URL']),
            $this->property('image', ['URL', 'ImageObject'], multiple: true),
            $this->property('video', ['VideoObject']),
            $this->property('position', ['Integer']),
        ];
    }
}
