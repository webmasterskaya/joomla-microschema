<?php

declare(strict_types=1);

use Joomla\Component\Microschema\Administrator\Schema\SchemaDataBuilder;

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
assertModuleSchemaSame(0, $schema['commentCount'], 'Integer zero must be preserved.');
assertModuleSchemaSame(false, $schema['isAccessibleForFree'], 'Boolean false must be preserved.');
assertModuleSchemaSame(false, array_key_exists('description', $schema), 'An empty string must be omitted.');
assertModuleSchemaSame(false, array_key_exists('unused', $schema), 'Null must be omitted.');

echo "Module schema builder tests passed.\n";
