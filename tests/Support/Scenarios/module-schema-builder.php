<?php

declare(strict_types=1);

use Joomla\Component\Microschema\Administrator\Schema\SchemaDataBuilder;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\AggregateRating;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\Article;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\ImageObject;

require_once __DIR__ . '/../../../com_microschema/administrator/src/Metadata/Format.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/Metadata/PropertyDefinition.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/Metadata/DescriptorInterface.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/Metadata/AbstractDescriptor.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/Metadata/SchemaOrg/AggregateRating.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/Metadata/SchemaOrg/Article.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/Metadata/SchemaOrg/ImageObject.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/Schema/SchemaDataBuilder.php';

function assertModuleSchemaSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . PHP_EOL
            . 'Expected: ' . var_export($expected, true) . PHP_EOL
            . 'Actual: ' . var_export($actual, true),
        );
    }
}

$builder = new SchemaDataBuilder(['Organization', 'ImageObject']);

assertModuleSchemaSame([], $builder->build('', ['name' => 'Ignored']), 'An empty schema type must produce no schema.');

$schema = $builder->build('Article', [
    'headline' => 'News',
    'publisher' => [
        'type' => 'Organization',
        'data' => [
            'name' => 'Publisher',
        ],
    ],
    'mainEntityOfPage' => [
        'type' => '@id',
        'data' => 'https://example.test/news/news-slug/#webpage',
    ],
    'author' => [
        'row0' => [
            'type' => '@id',
            'data' => 'https://example.test/#organization',
        ],
        'row1' => [
            'type' => '@id',
            'data' => '',
        ],
    ],
    'image' => [
        'row0' => [
            'type' => 'URL',
            'data' => 'https://example.test/one.jpg',
        ],
        'row1' => [
            'type' => 'ImageObject',
            'data' => [
                'url' => 'https://example.test/two.jpg',
            ],
        ],
        'row2' => [
            'type' => 'URL',
            'data' => '',
        ],
    ],
    'keywords' => [
        [
            'type' => 'string',
            'data' => ['Joomla', 'Schema.org'],
        ],
    ],
    'commentCount' => 0,
    'isAccessibleForFree' => false,
    'description' => '',
    'unused' => null,
]);

assertModuleSchemaSame('Article', $schema['@type'], 'The selected type must become @type.');
assertModuleSchemaSame('News', $schema['headline'], 'A scalar property must be preserved.');
assertModuleSchemaSame(
    ['@type' => 'Organization', 'name' => 'Publisher'],
    $schema['publisher'],
    'A registered object type must become a nested Schema.org object.',
);
assertModuleSchemaSame(
    ['@id' => 'https://example.test/news/news-slug/#webpage'],
    $schema['mainEntityOfPage'],
    'A reference value must become a JSON-LD @id object.',
);
assertModuleSchemaSame(
    [
        ['@id' => 'https://example.test/#organization'],
    ],
    $schema['author'],
    'A reference collection must become a filtered list of JSON-LD @id objects.',
);
assertModuleSchemaSame(
    [
        'https://example.test/one.jpg',
        ['@type' => 'ImageObject', 'url' => 'https://example.test/two.jpg'],
    ],
    $schema['image'],
    'Repeatable typed values must become a filtered list.',
);
assertModuleSchemaSame(
    ['Joomla', 'Schema.org'],
    $schema['keywords'],
    'A list-valued data source must expand into a repeatable scalar property.',
);
assertModuleSchemaSame(0, $schema['commentCount'], 'Integer zero must be preserved.');
assertModuleSchemaSame(false, $schema['isAccessibleForFree'], 'Boolean false must be preserved.');
assertModuleSchemaSame(false, array_key_exists('description', $schema), 'An empty string must be omitted.');
assertModuleSchemaSame(false, array_key_exists('unused', $schema), 'Null must be omitted.');

$typedBuilder = new SchemaDataBuilder([
    'AggregateRating' => AggregateRating::class,
    'Article' => Article::class,
    'ImageObject' => ImageObject::class,
]);
$typedSchema = $typedBuilder->build('Article', [
    'image' => [[
        'type' => 'ImageObject',
        'data' => [
            'url' => 'https://example.test/image.jpg',
            'width' => '1200',
            'height' => '{article.image_height}',
            'representativeOfPage' => '0',
        ],
    ]],
    'isAccessibleForFree' => '1',
]);

assertModuleSchemaSame(
    [[
        '@type' => 'ImageObject',
        'url' => 'https://example.test/image.jpg',
        'width' => 1200,
        'height' => '{article.image_height}',
        'representativeOfPage' => false,
    ]],
    $typedSchema['image'],
    'Nested Schema.org scalar values must follow their declared types without resolving data-source templates.',
);
assertModuleSchemaSame(true, $typedSchema['isAccessibleForFree'], 'Boolean strings must become booleans.');

$rating = $typedBuilder->build('AggregateRating', [
    'ratingValue' => '4.5',
    'ratingCount' => '12',
    'reviewCount' => '12.5',
    'bestRating' => '{rating.best}',
]);

assertModuleSchemaSame(4.5, $rating['ratingValue'], 'Number strings must become floats.');
assertModuleSchemaSame(12, $rating['ratingCount'], 'Integer strings must become integers.');
assertModuleSchemaSame('12.5', $rating['reviewCount'], 'Invalid integer strings must not be truncated.');
assertModuleSchemaSame('{rating.best}', $rating['bestRating'], 'Numeric data-source templates must remain strings until resolution.');

echo "Module schema builder tests passed.\n";
