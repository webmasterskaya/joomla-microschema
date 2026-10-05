<?php

declare(strict_types=1);

use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\Article;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\BlogPosting;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\Organization;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\WebPage;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\WebSite;
use Joomla\Component\Microschema\Administrator\Schema\SchemaLanguageEnricher;

require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/Format.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/PropertyDefinition.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/DescriptorInterface.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/AbstractDescriptor.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/SchemaOrg/Article.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/SchemaOrg/BlogPosting.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/SchemaOrg/Organization.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/SchemaOrg/WebPage.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/SchemaOrg/WebSite.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Schema/SchemaLanguageEnricher.php';

$enricher    = new SchemaLanguageEnricher();
$descriptors = [
    'Article'      => Article::class,
    'BlogPosting'  => BlogPosting::class,
    'Organization' => Organization::class,
    'WebPage'      => WebPage::class,
    'WebSite'      => WebSite::class,
];
$schemas     = $enricher->enrich([
    'article' => [
        '@type'      => 'Article',
        'headline'   => 'Example',
        'inLanguage' => 'de-DE',
        'publisher'  => [
            '@type' => 'Organization',
            'name'  => 'Publisher',
        ],
        'mainEntityOfPage' => [
            '@type' => ['Thing', 'WebPage'],
            'name'  => 'Example page',
        ],
    ],
    'website' => [
        '@type' => 'WebSite',
        'name'  => 'Example site',
    ],
], $descriptors, 'ru-RU');

if (($schemas['article']['inLanguage'] ?? null) !== 'ru-RU') {
    throw new RuntimeException('The Joomla language must replace a stored Article language.');
}

if (($schemas['article']['mainEntityOfPage']['inLanguage'] ?? null) !== 'ru-RU') {
    throw new RuntimeException('Nested language-aware schemas must receive the Joomla language.');
}

if (isset($schemas['article']['publisher']['inLanguage'])) {
    throw new RuntimeException('Schemas without automatic inLanguage must remain unchanged.');
}

if (($schemas['website']['inLanguage'] ?? null) !== 'ru-RU') {
    throw new RuntimeException('WebSite must receive the Joomla language.');
}

if ($enricher->enrich($schemas, $descriptors, '') !== $schemas) {
    throw new RuntimeException('An empty Joomla language must leave schemas unchanged.');
}

echo "Schema language enricher tests passed.\n";
