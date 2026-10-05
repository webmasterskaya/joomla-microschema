<?php

declare(strict_types=1);

use Joomla\Component\Microschema\Administrator\Metadata\PropertyDefinition;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\Article;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\BreadcrumbList;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\CollectionPage;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\ItemList;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\ListItem;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\Organization;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\PostalAddress;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\WebPage;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\WebSite;

require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/Format.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/PropertyDefinition.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/DescriptorInterface.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/AbstractDescriptor.php';

foreach (['Article', 'Organization', 'PostalAddress', 'WebSite', 'WebPage', 'CollectionPage', 'ItemList', 'ListItem', 'BreadcrumbList'] as $type) {
    require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/SchemaOrg/' . $type . '.php';
}

function newsSchemaProperties(object $descriptor): array
{
    $indexed = [];

    foreach ($descriptor->getProperties() as $property) {
        if (!$property instanceof PropertyDefinition) {
            throw new RuntimeException('Every descriptor property must be a PropertyDefinition.');
        }

        $indexed[$property->name] = $property;
    }

    return $indexed;
}

$expectedTypes = [
    WebSite::class => 'WebSite',
    WebPage::class => 'WebPage',
    CollectionPage::class => 'CollectionPage',
    ItemList::class => 'ItemList',
    ListItem::class => 'ListItem',
    PostalAddress::class => 'PostalAddress',
];

foreach ($expectedTypes as $class => $name) {
    if ((new $class())->getName() !== $name) {
        throw new RuntimeException($name . ' exposes an invalid Schema.org type name.');
    }
}

$article = newsSchemaProperties(new Article());

foreach (['url', 'inLanguage', 'isAccessibleForFree'] as $name) {
    if (!isset($article[$name])) {
        throw new RuntimeException('Article must expose ' . $name . '.');
    }
}

if (!$article['inLanguage']->automatic) {
    throw new RuntimeException('Article inLanguage must be populated automatically.');
}

$organization = newsSchemaProperties(new Organization());

if (!isset($organization['legalName']) || $organization['address']->types !== ['PostalAddress']) {
    throw new RuntimeException('Organization must expose legalName and PostalAddress.');
}

$webPage = newsSchemaProperties(new WebPage());
$website = newsSchemaProperties(new WebSite());

if (!$webPage['inLanguage']->automatic || !$website['inLanguage']->automatic) {
    throw new RuntimeException('WebPage and WebSite inLanguage must be populated automatically.');
}

if ($website['publisher']->types !== ['Organization', '@id']) {
    throw new RuntimeException('WebSite publisher must support an Organization object or an @id reference.');
}

if ($webPage['isPartOf']->types !== ['WebSite', '@id']) {
    throw new RuntimeException('WebPage isPartOf must support a WebSite object or an @id reference.');
}

if ($webPage['about']->types !== ['Organization', '@id']) {
    throw new RuntimeException('WebPage about must support an Organization object or an @id reference.');
}

if ($webPage['breadcrumb']->types !== ['BreadcrumbList', '@id']) {
    throw new RuntimeException('WebPage breadcrumb must support a BreadcrumbList object or an @id reference.');
}

if ($webPage['mainEntity']->types !== ['ItemList', 'NewsArticle', 'BlogPosting']) {
    throw new RuntimeException('WebPage mainEntity has an invalid definition.');
}

$itemList = newsSchemaProperties(new ItemList());

if ($itemList['itemListElement']->types !== ['ListItem'] || !$itemList['itemListElement']->multiple) {
    throw new RuntimeException('ItemList must contain multiple ListItem values.');
}

$listItem = newsSchemaProperties(new ListItem());

if (!$listItem['position']->required || $listItem['position']->types !== ['Integer']) {
    throw new RuntimeException('ListItem position must be a required Integer.');
}

$breadcrumb = newsSchemaProperties(new BreadcrumbList());

if ($breadcrumb['itemListElement']->types !== ['ListItem']) {
    throw new RuntimeException('BreadcrumbList must use ListItem values.');
}

echo "News schema metadata tests passed.\n";
