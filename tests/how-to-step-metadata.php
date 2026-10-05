<?php

declare(strict_types=1);

use Joomla\Component\Microschema\Administrator\Metadata\PropertyDefinition;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\HowToStep;

require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/Format.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/PropertyDefinition.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/DescriptorInterface.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/AbstractDescriptor.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/SchemaOrg/HowToStep.php';

$descriptor = new HowToStep();

if ($descriptor->getName() !== 'HowToStep') {
    throw new RuntimeException('The descriptor must expose the HowToStep Schema.org type.');
}

$properties = [];

foreach ($descriptor->getProperties() as $property) {
    if (!$property instanceof PropertyDefinition) {
        throw new RuntimeException('Every HowToStep property must be a PropertyDefinition.');
    }

    $properties[$property->name] = $property;
}

$expectedNames = ['name', 'text', 'url', 'image', 'video', 'position'];

if (array_keys($properties) !== $expectedNames) {
    throw new RuntimeException('HowToStep exposes an unexpected property set.');
}

if (!$properties['text']->required || $properties['text']->types !== ['string']) {
    throw new RuntimeException('HowToStep text must be a required string.');
}

if ($properties['url']->types !== ['URL']) {
    throw new RuntimeException('HowToStep url must use the URL type.');
}

if ($properties['image']->types !== ['URL', 'ImageObject'] || !$properties['image']->multiple) {
    throw new RuntimeException('HowToStep image must accept multiple URL or ImageObject values.');
}

if ($properties['video']->types !== ['VideoObject']) {
    throw new RuntimeException('HowToStep video must use the VideoObject type.');
}

if ($properties['position']->types !== ['Integer']) {
    throw new RuntimeException('HowToStep position must use the Integer type.');
}

echo "HowToStep metadata tests passed.\n";
