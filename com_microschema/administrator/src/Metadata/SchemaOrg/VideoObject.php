<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class VideoObject extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'VideoObject';
    }

    public function getProperties(): array
    {
        return [
            $this->property('name', required: true), $this->property('description', required: true),
            $this->property('thumbnailUrl', ['URL'], required: true, multiple: true),
            $this->property('uploadDate', ['Date', 'DateTime'], required: true),
            $this->property('duration', ['Duration']), $this->property('contentUrl', ['URL']),
            $this->property('embedUrl', ['URL']), $this->property('publisher', ['Organization']),
        ];
    }
}
