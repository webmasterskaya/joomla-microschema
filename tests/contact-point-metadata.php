<?php

declare(strict_types=1);

use Joomla\Component\Microschema\Administrator\Metadata\PropertyDefinition;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\ContactPoint;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\JobPosting;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\LocalBusiness;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\Organization;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\Person;

require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/Format.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/PropertyDefinition.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/DescriptorInterface.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/AbstractDescriptor.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/SchemaOrg/ContactPoint.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/SchemaOrg/JobPosting.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/SchemaOrg/Organization.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/SchemaOrg/LocalBusiness.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/SchemaOrg/Person.php';

function indexProperties(array $properties): array
{
    $indexed = [];

    foreach ($properties as $property) {
        if (!$property instanceof PropertyDefinition) {
            throw new RuntimeException('Every descriptor property must be a PropertyDefinition.');
        }

        $indexed[$property->name] = $property;
    }

    return $indexed;
}

$contactPoint = new ContactPoint();

if ($contactPoint->getName() !== 'ContactPoint') {
    throw new RuntimeException('The descriptor must expose the ContactPoint Schema.org type.');
}

$properties    = indexProperties($contactPoint->getProperties());
$expectedNames = [
    'contactType',
    'telephone',
    'email',
    'faxNumber',
    'areaServed',
    'availableLanguage',
    'contactOption',
    'hoursAvailable',
    'productSupported',
];

if (array_keys($properties) !== $expectedNames) {
    throw new RuntimeException('ContactPoint exposes an unexpected property set.');
}

if ($properties['areaServed']->types !== ['AdministrativeArea', 'GeoShape', 'Place', 'string'] || !$properties['areaServed']->multiple) {
    throw new RuntimeException('ContactPoint areaServed has an invalid definition.');
}

if ($properties['availableLanguage']->types !== ['Language', 'string'] || !$properties['availableLanguage']->multiple) {
    throw new RuntimeException('ContactPoint availableLanguage has an invalid definition.');
}

if ($properties['contactOption']->types !== ['ContactPointOption'] || !$properties['contactOption']->multiple) {
    throw new RuntimeException('ContactPoint contactOption has an invalid definition.');
}

if ($properties['hoursAvailable']->types !== ['OpeningHoursSpecification'] || !$properties['hoursAvailable']->multiple) {
    throw new RuntimeException('ContactPoint hoursAvailable has an invalid definition.');
}

if ($properties['productSupported']->types !== ['Product', 'string'] || !$properties['productSupported']->multiple) {
    throw new RuntimeException('ContactPoint productSupported has an invalid definition.');
}

$personProperties = indexProperties((new Person())->getProperties());

if ($personProperties['contactPoint']->types !== ['ContactPoint'] || !$personProperties['contactPoint']->multiple) {
    throw new RuntimeException('Person must accept multiple ContactPoint values.');
}

$organizationProperties = indexProperties((new Organization())->getProperties());

if ($organizationProperties['contactPoint']->types !== ['ContactPoint'] || !$organizationProperties['contactPoint']->multiple) {
    throw new RuntimeException('Organization must accept multiple ContactPoint values.');
}

$localBusinessProperties = indexProperties((new LocalBusiness())->getProperties());

if ($localBusinessProperties['contactPoint']->types !== ['ContactPoint'] || !$localBusinessProperties['contactPoint']->multiple) {
    throw new RuntimeException('LocalBusiness must inherit multiple ContactPoint values.');
}

$jobPostingProperties = indexProperties((new JobPosting())->getProperties());

if ($jobPostingProperties['applicationContact']->types !== ['ContactPoint']) {
    throw new RuntimeException('JobPosting applicationContact must use the ContactPoint type.');
}

echo "ContactPoint metadata tests passed.\n";
