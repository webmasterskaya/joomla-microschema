<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\Social;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;
use Joomla\Component\Microschema\Administrator\Metadata\Format;

final class TwitterCard extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'TwitterCard';
    }

    public function getFormat(): Format
    {
        return Format::META;
    }

    public function getProperties(): array
    {
        return [
            $this->property('card', required: true, tag: 'twitter:card'),
            $this->property('site', tag: 'twitter:site'), $this->property('creator', tag: 'twitter:creator'),
            $this->property('title', required: true, tag: 'twitter:title'),
            $this->property('description', tag: 'twitter:description'),
            $this->property('image', ['URL'], required: true, tag: 'twitter:image'),
            $this->property('imageAlt', tag: 'twitter:image:alt'),
            $this->property('player', ['URL'], tag: 'twitter:player'),
        ];
    }
}
