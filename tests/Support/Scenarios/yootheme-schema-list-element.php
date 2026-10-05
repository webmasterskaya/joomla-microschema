<?php

declare(strict_types=1);

use Joomla\Plugin\System\MicroschemaYootheme\Builder\SchemaListPayloadBuilder;
use Joomla\Plugin\System\MicroschemaYootheme\Builder\SchemaElementFieldBuilder;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\BlogPosting;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\NewsArticle;

define('_JEXEC', 1);

require_once __DIR__.'/../../../com_microschema/administrator/src/Schema/SchemaDataBuilder.php';
require_once __DIR__.'/../../../com_microschema/administrator/src/Metadata/Format.php';
require_once __DIR__.'/../../../com_microschema/administrator/src/Metadata/PropertyDefinition.php';
require_once __DIR__.'/../../../com_microschema/administrator/src/Metadata/DescriptorInterface.php';
require_once __DIR__.'/../../../com_microschema/administrator/src/Metadata/AbstractDescriptor.php';
require_once __DIR__.'/../../../com_microschema/administrator/src/Metadata/SchemaOrg/Article.php';
require_once __DIR__.'/../../../com_microschema/administrator/src/Metadata/SchemaOrg/BlogPosting.php';
require_once __DIR__.'/../../../com_microschema/administrator/src/Metadata/SchemaOrg/NewsArticle.php';
require_once __DIR__.'/../../../plg_system_microschemayootheme/src/Builder/SchemaElementFieldBuilder.php';
require_once __DIR__.'/../../../plg_system_microschemayootheme/src/Builder/SchemaListPayloadBuilder.php';

function assertYoothemeSchemaListSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message.PHP_EOL
            .'Expected: '.var_export($expected, true).PHP_EOL
            .'Actual: '.var_export($actual, true),
        );
    }
}

$builder = new SchemaListPayloadBuilder();
$registeredTypes = ['BlogPosting', 'BreadcrumbList', 'ItemList', 'ListItem', 'NewsArticle'];
$schemas = [
    'BlogPosting' => BlogPosting::class,
    'NewsArticle' => NewsArticle::class,
];
$itemFieldBuilder = new SchemaElementFieldBuilder(
    $schemas,
    [],
    'item_type',
    'item_schema',
);
$blogUrlField = $itemFieldBuilder->getFieldName('BlogPosting', 'url');
$blogHeadlineField = $itemFieldBuilder->getFieldName('BlogPosting', 'headline');
$blogImageField = $itemFieldBuilder->getFieldName('BlogPosting', 'image');
$children = [
    (object) ['props' => [
        'title' => 'First',
        'url' => 'https://example.ru/blog/article-slug/',
        'item_type' => 'BlogPosting',
        'item_id' => 'https://example.ru/blog/article-slug/#article',
        $blogUrlField => 'https://example.ru/blog/article-slug/',
        $blogHeadlineField => 'Заголовок статьи',
        $blogImageField => 'https://example.ru/images/article.jpg',
    ]],
    (object) ['props' => []],
    (object) ['props' => ['title' => 'Disabled', 'status' => false]],
    (object) ['props' => [
        'title' => 'Second',
        'item' => ['@type' => 'NewsArticle', 'headline' => 'Legacy article'],
    ]],
];

assertYoothemeSchemaListSame(
    [
        '@type' => 'ItemList',
        'name' => 'Articles',
        'numberOfItems' => 2,
        'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'First',
                'url' => 'https://example.ru/blog/article-slug/',
                'item' => [
                    '@type' => 'BlogPosting',
                    '@id' => 'https://example.ru/blog/article-slug/#article',
                    'headline' => 'Заголовок статьи',
                    'url' => 'https://example.ru/blog/article-slug/',
                    'image' => ['https://example.ru/images/article.jpg'],
                ],
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'Second',
                'item' => ['@type' => 'NewsArticle', 'headline' => 'Legacy article'],
            ],
        ],
    ],
    $builder->build('ItemList', [
        'name' => 'Builder element name',
        'list_name' => 'Articles',
        'item_list_order' => 'https://schema.org/ItemListOrderAscending',
    ], $children, $registeredTypes, $schemas),
    'ItemList must contain normalized, sequential ListItem entries and use list_name for its schema name.',
);

assertYoothemeSchemaListSame(
    [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'First',
                'url' => 'https://example.ru/blog/article-slug/',
                'item' => [
                    '@type' => 'BlogPosting',
                    '@id' => 'https://example.ru/blog/article-slug/#article',
                    'headline' => 'Заголовок статьи',
                    'url' => 'https://example.ru/blog/article-slug/',
                    'image' => ['https://example.ru/images/article.jpg'],
                ],
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'Second',
                'item' => ['@type' => 'NewsArticle', 'headline' => 'Legacy article'],
            ],
        ],
    ],
    $builder->build('BreadcrumbList', [
        'name' => 'Builder element name',
        'list_name' => 'Ignored',
    ], $children, $registeredTypes, $schemas),
    'BreadcrumbList must only contain its ListItem entries.',
);

assertYoothemeSchemaListSame(
    [
        '@type' => 'ItemList',
        'numberOfItems' => 1,
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'First',
                'url' => 'https://example.ru/blog/article-slug/',
                'item' => [
                    '@type' => 'BlogPosting',
                    '@id' => 'https://example.ru/blog/article-slug/#article',
                    'headline' => 'Заголовок статьи',
                    'url' => 'https://example.ru/blog/article-slug/',
                    'image' => ['https://example.ru/images/article.jpg'],
                ],
            ],
        ],
    ],
    $builder->build('ItemList', ['name' => 'Builder element name'], [$children[0]], $registeredTypes, $schemas),
    'The Builder element name must not be used as the ItemList schema name.',
);

assertYoothemeSchemaListSame(
    [],
    $builder->build('ItemList', [], [], $registeredTypes),
    'An empty list must not register structured data.',
);

assertYoothemeSchemaListSame(
    [],
    $builder->build('Article', [], $children, $registeredTypes),
    'The list element must reject unsupported root schema types.',
);

assertYoothemeSchemaListSame(
    [
        '@type' => 'ItemList',
        'numberOfItems' => 1,
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'item' => 'https://example.ru/catalog/item/',
            ],
        ],
    ],
    $builder->build('ItemList', [], [
        (object) ['props' => [
            'item_type' => 'URL',
            'item_url' => 'https://example.ru/catalog/item/',
        ]],
    ], $registeredTypes, $schemas),
    'A ListItem must support a URL as its item value.',
);

echo "YOOtheme schema list element tests passed.\n";
