<?php

declare(strict_types=1);

namespace Joomla\CMS\Language {
    final class Text
    {
        public static function _(string $key): string
        {
            return $key;
        }
    }
}

namespace {
    use Joomla\Component\Microschema\Administrator\DataCollection\DataCollectionInterface;
    use Joomla\Component\Microschema\Administrator\DataCollection\DataCollectionRegistry;
    use Joomla\Component\Microschema\Administrator\DataCollection\DataCollectionResult;
    use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
    use Joomla\Component\Microschema\Administrator\DataSource\DataSourceCatalogBuilder;
    use Joomla\Component\Microschema\Administrator\DataSource\DataSourceField;
    use Joomla\Component\Microschema\Administrator\DataSource\DataSourceInterface;
    use Joomla\Component\Microschema\Administrator\DataSource\DataSourceRegistry;
    use Joomla\Component\Microschema\Administrator\DataSource\DataSourceTemplateResolver;
    use Joomla\Component\Microschema\Administrator\DataSource\DataTypeInterface;
    use Joomla\Component\Microschema\Administrator\DataSource\DataTypeRegistry;

    require_once __DIR__ . '/../com_microschema/administrator/src/DataCollection/CollectionIteration.php';
    require_once __DIR__ . '/../com_microschema/administrator/src/DataCollection/DataCollectionInterface.php';
    require_once __DIR__ . '/../com_microschema/administrator/src/DataCollection/DataCollectionRegistry.php';
    require_once __DIR__ . '/../com_microschema/administrator/src/DataCollection/DataCollectionResult.php';
    require_once __DIR__ . '/../com_microschema/administrator/src/DataSource/DataContext.php';
    require_once __DIR__ . '/../com_microschema/administrator/src/DataSource/DataSourceField.php';
    require_once __DIR__ . '/../com_microschema/administrator/src/DataSource/DataSourceInterface.php';
    require_once __DIR__ . '/../com_microschema/administrator/src/DataSource/DataSourceRegistry.php';
    require_once __DIR__ . '/../com_microschema/administrator/src/DataSource/DataTypeInterface.php';
    require_once __DIR__ . '/../com_microschema/administrator/src/DataSource/DataTypeRegistry.php';
    require_once __DIR__ . '/../com_microschema/administrator/src/DataSource/DataSourceCatalogBuilder.php';
    require_once __DIR__ . '/../com_microschema/administrator/src/DataSource/DataSourceTemplateResolver.php';

    function assertCollectionResolverSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message);
        }
    }

    $sourceRegistry = new DataSourceRegistry();
    $sourceRegistry->register(new class () implements DataSourceInterface {
        public function getName(): string { return 'article'; }
        public function getLabel(): string { return 'Article'; }
        public function supportsContext(string $context): bool { return $context === 'com_content.article'; }
        public function getType(): string { return 'Article'; }
        public function getValue(DataContext $context): mixed { return $context->item; }
    });
    $typeRegistry = new DataTypeRegistry();
    $typeRegistry->register(new class () implements DataTypeInterface {
        public function getName(): string { return 'Article'; }
        public function getFields(mixed $value, DataContext $context): array
        {
            return [new DataSourceField('title', 'Title')];
        }
        public function resolve(mixed $value, string $field, DataContext $context): mixed
        {
            return is_array($value) ? ($value[$field] ?? null) : null;
        }
    });
    $collectionRegistry = new DataCollectionRegistry();
    $articleCollection = new class () implements DataCollectionInterface {
        public int $calls = 0;
        public function getName(): string { return 'content.category.articles'; }
        public function getLabel(): string { return 'Category articles'; }
        public function supportsContext(string $context): bool { return $context === 'com_content.categories'; }
        public function getPreviewContext(DataContext $context): DataContext
        {
            return new DataContext('com_content.article', 0, []);
        }
        public function getItems(DataContext $context): DataCollectionResult
        {
            $this->calls++;

            return new DataCollectionResult([
                new DataContext('com_content.article', 10, ['title' => 'First']),
                new DataContext('com_content.article', 11, ['title' => 'Second']),
            ], 25, 60);
        }
    };
    $collectionRegistry->register($articleCollection);
    $messages = [];
    $resolver = new DataSourceTemplateResolver(
        $sourceRegistry,
        $typeRegistry,
        new DataSourceCatalogBuilder($sourceRegistry, $typeRegistry),
        $collectionRegistry,
        static function (string $message) use (&$messages): void {
            $messages[] = $message;
        },
    );
    $context = new DataContext('com_content.categories', 5, ['title' => 'Blog']);
    $template = [[
        'collection' => 'content.category.articles',
        'type'       => 'ListItem',
        'data'       => [
            'position' => '{iteration.position}',
            'name'     => '{article.title}',
        ],
    ]];

    assertCollectionResolverSame(
        [
            ['type' => 'ListItem', 'data' => ['position' => 26, 'name' => 'First']],
            ['type' => 'ListItem', 'data' => ['position' => 27, 'name' => 'Second']],
        ],
        $resolver->resolve($template, $context),
        'A collection template must expand once per item and preserve the pagination offset.',
    );
    $catalog = $resolver->getCatalog($context);
    assertCollectionResolverSame('content.category.articles', $catalog['collections'][0]['name'] ?? null, 'The collection must be exposed in the editor catalog.');
    assertCollectionResolverSame(
        ['article', 'iteration'],
        array_column($catalog['collections'][0]['dataSourceCatalog']['sources'] ?? [], 'name'),
        'A collection template must expose item and iteration sources.',
    );
    $collectionSource = array_values(array_filter(
        $catalog['sources'] ?? [],
        static fn (array $source): bool => str_starts_with($source['name'] ?? '', 'collection_content_category_articles_'),
    ))[0] ?? null;
    assertCollectionResolverSame('Category articles', $collectionSource['label'] ?? null, 'A collection summary must be exposed as a root data source.');
    assertCollectionResolverSame(
        ['count', 'total'],
        array_column($collectionSource['fields'] ?? [], 'name'),
        'A collection summary must expose current-page and total counts.',
    );

    $articleCollection->calls = 0;
    $collectionSourceName = $collectionSource['name'] ?? '';
    $resolvedList = $resolver->resolve([
        'numberOfItems' => sprintf('{%s.total}', $collectionSourceName),
        'currentPageItems' => sprintf('{%s.count}', $collectionSourceName),
        'itemListElement' => $template,
    ], $context);
    assertCollectionResolverSame(60, $resolvedList['numberOfItems'] ?? null, 'A collection total must resolve outside the item template.');
    assertCollectionResolverSame(2, $resolvedList['currentPageItems'] ?? null, 'A collection count must resolve outside the item template.');
    assertCollectionResolverSame(2, count($resolvedList['itemListElement'] ?? []), 'The collection template must still expand when its summary is used.');
    assertCollectionResolverSame(1, $articleCollection->calls, 'A collection summary and template must share one collection result per resolution.');
    assertCollectionResolverSame(null, $resolver->resolve('{iteration.position}', $context), 'Iteration placeholders must be strict outside collections.');
    assertCollectionResolverSame(true, count($messages) > 0, 'Invalid iteration usage must be logged.');

    echo "Data collection template resolver tests passed.\n";
}
