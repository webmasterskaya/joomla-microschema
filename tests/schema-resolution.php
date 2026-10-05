<?php

declare(strict_types=1);

use Joomla\Component\Microschema\Administrator\Schema\ContextNode;
use Joomla\Component\Microschema\Administrator\Schema\ContextPath;
use Joomla\Component\Microschema\Administrator\Schema\SchemaCandidate;
use Joomla\Component\Microschema\Administrator\Schema\SchemaRegistry;
use Joomla\Component\Microschema\Administrator\Schema\SchemaResolver;

require_once __DIR__ . '/../com_microschema/administrator/src/Schema/ContextNode.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Schema/ContextPath.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Schema/SchemaCandidate.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Schema/SchemaRegistry.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Schema/SchemaResolver.php';

function assertSchemaSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . PHP_EOL
            . 'Expected: ' . var_export($expected, true) . PHP_EOL
            . 'Actual: ' . var_export($actual, true),
        );
    }
}

$resolver = new SchemaResolver();

// An article is more specific than a menu item targeting one of its ancestor categories.
$category       = new ContextNode('com_content.category', 10);
$nestedCategory = new ContextNode('com_content.category', 15);
$article        = new ContextNode('com_content.article', 42);
$categoryMenu   = new ContextNode('com_menus.item', 100);
$path           = (new ContextPath())
    ->append($category)
    ->append($nestedCategory)
    ->append($article)
    ->insertAfter($category->getKey(), $categoryMenu);
$registry       = (new SchemaRegistry())
    ->add($category, new SchemaCandidate('primary', ['name' => 'Category']))
    ->add($categoryMenu, new SchemaCandidate('primary', ['name' => 'Category menu']))
    ->add($article, new SchemaCandidate('primary', ['name' => 'Article']));

assertSchemaSame('Article', $resolver->resolve($path, $registry)['primary']['name'], 'Article must override its category menu.');

// A menu item directly targeting an article is more specific than the article itself.
$articleMenu = new ContextNode('com_menus.item', 101);
$directPath  = (new ContextPath())
    ->append($category)
    ->append($article)
    ->insertAfter($article->getKey(), $articleMenu);
$registry->add($articleMenu, new SchemaCandidate('primary', ['name' => 'Article menu']));

assertSchemaSame('Article menu', $resolver->resolve($directPath, $registry)['primary']['name'], 'Direct article menu must override the article.');

// Store integrations can define their own hierarchy without changes to the resolver.
$shopCategory    = new ContextNode('com_shop.category', 20);
$shopSubcategory = new ContextNode('com_shop.category', 21);
$metaProduct     = new ContextNode('com_shop.product', 73);
$variant         = new ContextNode('com_shop.variant', 81);
$shopMenu        = new ContextNode('com_menus.item', 200);
$shopPath        = (new ContextPath())
    ->append($shopCategory)
    ->append($shopSubcategory)
    ->append($metaProduct)
    ->append($variant)
    ->insertAfter($shopCategory->getKey(), $shopMenu);
$shopRegistry    = (new SchemaRegistry())
    ->add($shopCategory, new SchemaCandidate('primary', ['name' => 'Shop category']))
    ->add($shopMenu, new SchemaCandidate('primary', ['name' => 'Shop menu']))
    ->add($metaProduct, new SchemaCandidate('primary', ['name' => 'Meta product']))
    ->add($variant, new SchemaCandidate('primary', ['name' => 'Variant', 'stock' => 0, 'active' => false, 'note' => '']));
$shopResult      = $resolver->resolve($shopPath, $shopRegistry);

assertSchemaSame('Variant', $shopResult['primary']['name'], 'Variant must override its ancestor contexts.');
assertSchemaSame(0, $shopResult['primary']['stock'], 'Integer zero must be preserved.');
assertSchemaSame(false, $shopResult['primary']['active'], 'Boolean false must be preserved.');
assertSchemaSame('', $shopResult['primary']['note'], 'Empty string must be preserved.');

// A direct menu for a variant overrides the variant and null does not erase inherited data.
$variantMenu = new ContextNode('com_menus.item', 201);
$variantPath = (new ContextPath())
    ->append($shopCategory)
    ->append($shopSubcategory)
    ->append($metaProduct)
    ->append($variant)
    ->insertAfter($variant->getKey(), $variantMenu);
$shopRegistry->add($variantMenu, new SchemaCandidate('primary', ['name' => 'Variant menu', 'stock' => null]));
$variantResult = $resolver->resolve($variantPath, $shopRegistry);

assertSchemaSame('Variant menu', $variantResult['primary']['name'], 'Direct variant menu must override the variant.');
assertSchemaSame(0, $variantResult['primary']['stock'], 'Null must not erase an inherited value.');

// Priority resolves candidates inside one context; distinct UIDs remain separate schemas.
$priorityRegistry = (new SchemaRegistry())
    ->add($article, new SchemaCandidate('primary', ['name' => 'High'], 10))
    ->add($article, new SchemaCandidate('primary', ['name' => 'Low'], -10))
    ->add($article, new SchemaCandidate('breadcrumb', ['@type' => 'BreadcrumbList']));
$priorityResult = $resolver->resolve((new ContextPath())->append($article), $priorityRegistry);

assertSchemaSame('High', $priorityResult['primary']['name'], 'Higher local priority must win.');
assertSchemaSame('BreadcrumbList', $priorityResult['breadcrumb']['@type'], 'Distinct UIDs must remain separate.');

echo "Schema resolution tests passed.\n";
