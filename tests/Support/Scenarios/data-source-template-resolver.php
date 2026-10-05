<?php

declare(strict_types=1);

use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
use Joomla\Component\Microschema\Administrator\DataCollection\DataCollectionRegistry;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceField;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceCatalogBuilder;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceInterface;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceRegistry;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceTemplateResolver;
use Joomla\Component\Microschema\Administrator\DataSource\DataTypeInterface;
use Joomla\Component\Microschema\Administrator\DataSource\DataTypeRegistry;

require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataContext.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/DataCollection/DataCollectionRegistry.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataSourceField.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataSourceCatalogBuilder.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataSourceInterface.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataSourceRegistry.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataTypeInterface.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataTypeRegistry.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataSourceTemplateResolver.php';

function assertTemplateResolverSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
}

$articleSource = new class () implements DataSourceInterface {
    public function getName(): string
    {
        return 'article';
    }

    public function getLabel(): string
    {
        return 'Article';
    }

    public function supportsContext(string $context): bool
    {
        return $context === 'com_content.article';
    }

    public function getType(): string
    {
        return 'Article';
    }

    public function getValue(DataContext $context): mixed
    {
        return $context->item;
    }
};

$productSource = new class () implements DataSourceInterface {
    public function getName(): string
    {
        return 'product';
    }

    public function getLabel(): string
    {
        return 'Product';
    }

    public function supportsContext(string $context): bool
    {
        return $context === 'com_content.article';
    }

    public function getType(): string
    {
        return 'Product';
    }

    public function getValue(DataContext $context): mixed
    {
        return [
            'seller'  => $context->item['author'] ?? null,
            'details' => ['kind' => 'product'],
        ];
    }
};

$articleType = new class () implements DataTypeInterface {
    public function getName(): string
    {
        return 'Article';
    }

    public function getFields(mixed $value, DataContext $context): array
    {
        return [
            new DataSourceField('title', 'Title'),
            new DataSourceField('hits', 'Hits', 'Integer'),
            new DataSourceField('tags', 'Tags', 'List'),
            new DataSourceField('empty', 'Empty'),
            new DataSourceField('author', 'Author', 'User'),
            new DataSourceField('details', 'Details', 'DynamicFields'),
        ];
    }

    public function resolve(mixed $value, string $field, DataContext $context): mixed
    {
        return is_array($value) ? ($value[$field] ?? null) : null;
    }
};

$productType = new class () implements DataTypeInterface {
    public function getName(): string
    {
        return 'Product';
    }

    public function getFields(mixed $value, DataContext $context): array
    {
        return [
            new DataSourceField('seller', 'Seller', 'User'),
            new DataSourceField('details', 'Details', 'DynamicFields'),
        ];
    }

    public function resolve(mixed $value, string $field, DataContext $context): mixed
    {
        return is_array($value) ? ($value[$field] ?? null) : null;
    }
};

$userType = new class () implements DataTypeInterface {
    public function getName(): string
    {
        return 'User';
    }

    public function getFields(mixed $value, DataContext $context): array
    {
        return [
            new DataSourceField('name', 'Name'),
            new DataSourceField('id', 'ID', 'Integer'),
        ];
    }

    public function resolve(mixed $value, string $field, DataContext $context): mixed
    {
        return is_array($value) ? ($value[$field] ?? null) : null;
    }
};

$dynamicFieldsType = new class () implements DataTypeInterface {
    public function getName(): string
    {
        return 'DynamicFields';
    }

    public function getFields(mixed $value, DataContext $context): array
    {
        $kind = is_array($value) ? ($value['kind'] ?? '') : '';

        return $kind === '' ? [] : [new DataSourceField($kind . 'Field', ucfirst($kind) . ' field')];
    }

    public function resolve(mixed $value, string $field, DataContext $context): mixed
    {
        return is_array($value) ? ($value[$field] ?? null) : null;
    }
};

$sourceRegistry = new DataSourceRegistry();
$sourceRegistry->register($articleSource);
$sourceRegistry->register($productSource);
$typeRegistry = new DataTypeRegistry();
$typeRegistry->register($articleType);
$typeRegistry->register($productType);
$typeRegistry->register($userType);
$typeRegistry->register($dynamicFieldsType);
$messages = [];
$catalogBuilder = new DataSourceCatalogBuilder($sourceRegistry, $typeRegistry);
$resolver = new DataSourceTemplateResolver(
    $sourceRegistry,
    $typeRegistry,
    $catalogBuilder,
    new DataCollectionRegistry(),
    static function (string $message) use (&$messages): void {
        $messages[] = $message;
    },
);
$context  = new DataContext(
    'com_content.article',
    42,
    [
        'title'   => 'Example',
        'hits'    => 7,
        'tags'    => ['one', 'two'],
        'empty'   => null,
        'author'  => ['id' => 9, 'name' => 'Ada'],
        'details' => ['kind' => 'article'],
    ],
);

assertTemplateResolverSame('Example', $resolver->resolve('{article.title}', $context), 'Exact placeholders must resolve.');
assertTemplateResolverSame(7, $resolver->resolve('{article.hits}', $context), 'Exact placeholders must preserve scalar types.');
assertTemplateResolverSame(['one', 'two'], $resolver->resolve('{article.tags}', $context), 'Exact placeholders must preserve arrays.');
assertTemplateResolverSame('Example — 7', $resolver->resolve('{article.title} — {article.hits}', $context), 'Mixed templates must interpolate scalars.');
assertTemplateResolverSame('Prefix ', $resolver->resolve('Prefix {article.empty}', $context), 'Valid empty placeholders must become empty strings in mixed templates.');
assertTemplateResolverSame(null, $resolver->resolve('{article.empty}', $context), 'An exact valid empty placeholder must preserve null.');
assertTemplateResolverSame(null, $resolver->resolve('Tags: {article.tags}', $context), 'Mixed templates must reject arrays.');
assertTemplateResolverSame(null, $resolver->resolve('{missing.value}', $context), 'Unknown sources must resolve to null.');
assertTemplateResolverSame(null, $resolver->resolve('Invalid {missing.value}', $context), 'Invalid placeholders must reject the complete mixed template.');
assertTemplateResolverSame(null, $resolver->resolve('{article.secret}', $context), 'Undeclared fields must be rejected.');
assertTemplateResolverSame(null, $resolver->resolve('{article.author}', $context), 'Object references must not be terminal values.');
assertTemplateResolverSame('Ada', $resolver->resolve('{article.author.name}', $context), 'Referenced types must resolve nested fields.');
assertTemplateResolverSame('Ada', $resolver->resolve('{product.seller.name}', $context), 'Different sources must reuse the same object type.');
assertTemplateResolverSame(
    ['headline' => 'Example', 'nested' => ['hits' => 7]],
    $resolver->resolve(['headline' => '{article.title}', 'nested' => ['hits' => '{article.hits}']], $context),
    'Nested schema values must resolve recursively.',
);

$catalog = $resolver->getCatalog($context);

assertTemplateResolverSame('article', $catalog['sources'][0]['name'] ?? null, 'Catalog sources must retain their public names.');
assertTemplateResolverSame('User', $catalog['sources'][0]['fields'][4]['type'] ?? null, 'Object fields must reference reusable types.');
assertTemplateResolverSame(
    'name',
    $catalog['sources'][0]['fields'][4]['fields'][0]['name'] ?? null,
    'Referenced types must publish path-specific nested fields.',
);
$productCatalog = array_values(array_filter(
    $catalog['sources'],
    static fn (array $source): bool => ($source['name'] ?? '') === 'product',
))[0] ?? [];
assertTemplateResolverSame(
    'articleField',
    $catalog['sources'][0]['fields'][5]['fields'][0]['name'] ?? null,
    'Dynamic fields must be described from the value at the article path.',
);
assertTemplateResolverSame(
    'productField',
    $productCatalog['fields'][1]['fields'][0]['name'] ?? null,
    'The same dynamic type must expose different fields at another path.',
);
assertTemplateResolverSame([], $resolver->getCatalog($context, ['article', 'product'])['sources'], 'Excluded sources must be hidden.');

$sourceRegistry->register(new class () implements DataSourceInterface {
    public function getName(): string
    {
        return 'article';
    }

    public function getLabel(): string
    {
        return 'Contact article';
    }

    public function supportsContext(string $context): bool
    {
        return $context === 'com_contact.contact';
    }

    public function getType(): string
    {
        return 'Article';
    }

    public function getValue(DataContext $context): mixed
    {
        return $context->item;
    }
});
assertTemplateResolverSame(
    'Article',
    $sourceRegistry->get('article', 'com_content.article')->getLabel(),
    'Equal source names must resolve independently by context.',
);
assertTemplateResolverSame(
    true,
    count(array_filter($messages, static fn (string $message): bool => str_contains($message, 'valid but resolved to an empty value'))) >= 2,
    'Valid empty values must be explained in the debug log.',
);
assertTemplateResolverSame(
    true,
    count(array_filter($messages, static fn (string $message): bool => str_contains($message, 'data source "missing" is not registered'))) >= 2,
    'Unknown sources must be explained in the debug log.',
);
assertTemplateResolverSame(
    true,
    count(array_filter($messages, static fn (string $message): bool => str_contains($message, 'returned a non-scalar value'))) >= 1,
    'Non-scalar mixed values must be explained in the debug log.',
);
assertTemplateResolverSame(false, str_contains(implode("\n", $messages), 'Ada'), 'Debug messages must not contain resolved source values.');

echo "Data source template resolver tests passed.\n";
