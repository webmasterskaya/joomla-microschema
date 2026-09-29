<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class PostalAddress extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'PostalAddress';
    }

    public function getProperties(): array
    {
        return [
            $this->property('streetAddress'),
            $this->property('postOfficeBoxNumber'),
            $this->property('addressLocality'),
            $this->property('addressRegion'),
            $this->property('postalCode'),
            $this->property('addressCountry'),
        ];
    }
}
