<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class Course extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'Course';
    }

    public function getProperties(): array
    {
        return [
            $this->property('name', required: true), $this->property('description', required: true),
            $this->property('provider', ['Organization', 'Person'], required: true),
            $this->property('hasCourseInstance', ['CourseInstance'], multiple: true),
            $this->property('offers', ['Offer'], multiple: true),
        ];
    }
}
