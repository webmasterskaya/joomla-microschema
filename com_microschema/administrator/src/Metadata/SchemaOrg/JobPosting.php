<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class JobPosting extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'JobPosting';
    }

    public function getProperties(): array
    {
        return [
            $this->property('title', required: true), $this->property('description', required: true),
            $this->property('datePosted', ['Date'], required: true), $this->property('validThrough', ['Date', 'DateTime']),
            $this->property('employmentType', ['string'], multiple: true),
            $this->property('hiringOrganization', ['Organization'], required: true),
            $this->property('jobLocation', ['Place'], multiple: true),
            $this->property('applicantLocationRequirements', ['AdministrativeArea'], multiple: true),
            $this->property('jobLocationType'), $this->property('baseSalary', ['MonetaryAmount']),
            $this->property('applicationContact', ['ContactPoint']),
        ];
    }
}
