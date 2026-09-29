<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\Social;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;
use Joomla\Component\Microschema\Administrator\Metadata\Format;

class OpenGraph extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'OpenGraph';
    }

    public function getFormat(): Format
    {
        return Format::META;
    }

    public function getProperties(): array
    {
        return [
            $this->property('title', required: true, tag: 'og:title'),
            $this->property('type', required: true, tag: 'og:type'),
            $this->property('image', ['URL'], required: true, multiple: true, tag: 'og:image'),
            $this->property('url', ['URL'], required: true, tag: 'og:url'),
            $this->property('description', tag: 'og:description'),
            $this->property('siteName', tag: 'og:site_name'),
            $this->property('locale', tag: 'og:locale'),
            $this->property('imageAlt', tag: 'og:image:alt'),
            $this->property('video', ['URL'], multiple: true, tag: 'og:video'),
            $this->property('audio', ['URL'], multiple: true, tag: 'og:audio'),
        ];
    }
}
