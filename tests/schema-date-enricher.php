<?php

declare(strict_types=1);

use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\Article;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\BlogPosting;
use Joomla\Component\Microschema\Administrator\Schema\SchemaDateEnricher;

require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/Format.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/PropertyDefinition.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/DescriptorInterface.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/AbstractDescriptor.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/SchemaOrg/Article.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/SchemaOrg/BlogPosting.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Schema/SchemaDateEnricher.php';

function assertSchemaDateSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
}

$enricher = new SchemaDateEnricher();
$schemas  = $enricher->enrich([
    'list' => [
        '@type' => 'ItemList',
        'itemListElement' => [[
            '@type' => 'ListItem',
            'item'  => [
                '@type'       => 'BlogPosting',
                'headline'    => 'Example',
                'datePublished' => '2026-07-16 16:02:19',
                'dateModified'  => '2026-07-16T18:30:00+02:00',
            ],
        ], [
            '@type' => 'ListItem',
            'item'  => [
                '@type'        => 'Article',
                'headline'     => 'Date only',
                'datePublished' => '2026-07-15',
                'dateModified'  => '0000-00-00 00:00:00',
            ],
        ]],
    ],
], [
    'Article'     => Article::class,
    'BlogPosting' => BlogPosting::class,
], 'Europe/Moscow');

$first  = $schemas['list']['itemListElement'][0]['item'];
$second = $schemas['list']['itemListElement'][1]['item'];

assertSchemaDateSame('2026-07-16T19:02:19+03:00', $first['datePublished'] ?? null, 'A timezone-less Joomla DateTime must be interpreted as UTC and converted to the site timezone.');
assertSchemaDateSame('2026-07-16T19:30:00+03:00', $first['dateModified'] ?? null, 'An ISO DateTime must preserve its instant while using the site timezone.');
assertSchemaDateSame('2026-07-15', $second['datePublished'] ?? null, 'A Date value must remain a calendar date.');
assertSchemaDateSame(false, isset($second['dateModified']), 'A Joomla zero date must be removed.');

echo "Schema date enricher tests passed.\n";
