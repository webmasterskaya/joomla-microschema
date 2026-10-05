<?php

declare(strict_types=1);

use Joomla\Component\Microschema\Administrator\Schema\BreadcrumbListBuilder;

require_once __DIR__ . '/../com_microschema/administrator/src/Schema/BreadcrumbListBuilder.php';

function assertBreadcrumbBuilderSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . PHP_EOL
            . 'Expected: ' . var_export($expected, true) . PHP_EOL
            . 'Actual: ' . var_export($actual, true),
        );
    }
}

$builder = new BreadcrumbListBuilder();
$schema  = $builder->build([
    ['name' => 'Home', 'url' => 'https://example.test/'],
    ['name' => 'Category', 'url' => 'https://example.test/category'],
    ['name' => 'Duplicate category', 'url' => 'https://example.test/category'],
    ['name' => 'Article', 'url' => 'https://example.test/category/article'],
    ['name' => '', 'url' => 'https://example.test/ignored'],
]);

assertBreadcrumbBuilderSame('BreadcrumbList', $schema['@type'], 'The root schema type is invalid.');
assertBreadcrumbBuilderSame(
    [1, 2, 3],
    array_column($schema['itemListElement'], 'position'),
    'Breadcrumb positions must be contiguous.',
);
assertBreadcrumbBuilderSame(
    ['Home', 'Category', 'Article'],
    array_column($schema['itemListElement'], 'name'),
    'Invalid and duplicate breadcrumb entries must be removed.',
);
assertBreadcrumbBuilderSame([], $builder->build([]), 'An empty pathway must not produce a schema.');

echo "BreadcrumbList builder tests passed.\n";
