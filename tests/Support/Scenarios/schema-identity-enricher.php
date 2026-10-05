<?php

declare(strict_types=1);

use Joomla\Component\Microschema\Administrator\Schema\SchemaIdentityEnricher;

require_once __DIR__ . '/../../../com_microschema/administrator/src/Schema/SchemaIdentityEnricher.php';

function assertSchemaIdentitySame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
}

$enricher = new SchemaIdentityEnricher();
$schemas  = $enricher->enrich([
    'global.organization' => [
        '@type' => 'Organization',
        'name'  => 'Publisher',
        'logo'  => [
            '@type'     => 'ImageObject',
            'contentUrl' => 'https://example.test/images/logo.png',
        ],
    ],
    'global.website' => [
        '@type' => 'WebSite',
        'name'  => 'Example',
    ],
    'primary' => [
        '@type' => 'CollectionPage',
        'url'   => 'https://example.test/blog/#old-fragment',
        'mainEntity' => [
            '@type' => 'ItemList',
            'itemListElement' => [[
                '@type'    => 'ListItem',
                'position' => 1,
                'item'     => [
                    '@type' => 'BlogPosting',
                    'url'   => 'https://example.test/blog/post/',
                ],
            ]],
        ],
    ],
    'global.breadcrumb' => [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [],
    ],
], 'https://example.test/', 'https://example.test/blog/?start=20#fragment', true);

assertSchemaIdentitySame('https://example.test/#organization', $schemas['global.organization']['@id'] ?? null, 'Organization must receive a stable site identifier.');
assertSchemaIdentitySame('https://example.test/#logo', $schemas['global.organization']['logo']['@id'] ?? null, 'The organization logo must receive a stable site identifier.');
assertSchemaIdentitySame('https://example.test/#website', $schemas['global.website']['@id'] ?? null, 'WebSite must receive a stable site identifier.');
assertSchemaIdentitySame('https://example.test/blog/#webpage', $schemas['primary']['@id'] ?? null, 'CollectionPage must derive its identifier from its own URL.');
assertSchemaIdentitySame('https://example.test/blog/?start=20#itemlist', $schemas['primary']['mainEntity']['@id'] ?? null, 'A single ItemList must use the current page identifier.');
assertSchemaIdentitySame('https://example.test/blog/post/#article', $schemas['primary']['mainEntity']['itemListElement'][0]['item']['@id'] ?? null, 'BlogPosting must derive its identifier from its URL.');
assertSchemaIdentitySame('https://example.test/blog/?start=20#breadcrumb', $schemas['global.breadcrumb']['@id'] ?? null, 'BreadcrumbList must use the current page identifier.');
assertSchemaIdentitySame(
    ['@id' => 'https://example.test/blog/?start=20#breadcrumb'],
    $schemas['primary']['breadcrumb'] ?? null,
    'CollectionPage must reference the generated BreadcrumbList when breadcrumbs are enabled.',
);

$multipleLists = $enricher->enrich([
    'first'  => ['@type' => 'ItemList', 'itemListElement' => []],
    'second' => ['@type' => 'ItemList', 'itemListElement' => []],
], 'https://example.test/', 'https://example.test/');

assertSchemaIdentitySame('https://example.test/#itemlist-first', $multipleLists['first']['@id'] ?? null, 'Multiple ItemList objects must receive a stable UID suffix.');
assertSchemaIdentitySame('https://example.test/#itemlist-second', $multipleLists['second']['@id'] ?? null, 'Every ItemList UID suffix must be unique.');

$explicit = $enricher->enrich([
    'primary' => ['@type' => 'WebPage', '@id' => 'https://example.test/custom-id', 'url' => 'https://example.test/'],
], 'https://example.test/', 'https://example.test/');

assertSchemaIdentitySame('https://example.test/custom-id', $explicit['primary']['@id'] ?? null, 'An explicit identifier must not be replaced.');

$explicitBreadcrumb = $enricher->enrich([
    'page' => [
        '@type'     => 'WebPage',
        'url'       => 'https://example.test/custom/',
        'breadcrumb' => ['@id' => 'https://example.test/manually-selected-breadcrumb'],
    ],
    'breadcrumb' => [
        '@type' => 'BreadcrumbList',
        '@id'   => 'https://example.test/custom-breadcrumb',
        'itemListElement' => [],
    ],
], 'https://example.test/', 'https://example.test/custom/', true);

assertSchemaIdentitySame(
    ['@id' => 'https://example.test/manually-selected-breadcrumb'],
    $explicitBreadcrumb['page']['breadcrumb'] ?? null,
    'An explicit WebPage breadcrumb reference must not be replaced.',
);

$disabledBreadcrumb = $enricher->enrich([
    'page'       => ['@type' => 'CollectionPage', 'url' => 'https://example.test/disabled/'],
    'breadcrumb' => ['@type' => 'BreadcrumbList', 'itemListElement' => []],
], 'https://example.test/', 'https://example.test/disabled/', false);

assertSchemaIdentitySame(false, isset($disabledBreadcrumb['page']['breadcrumb']), 'A breadcrumb reference must not be added when the component setting is disabled.');

$missingBreadcrumb = $enricher->enrich([
    'page' => ['@type' => 'WebPage', 'url' => 'https://example.test/missing/'],
], 'https://example.test/', 'https://example.test/missing/', true);

assertSchemaIdentitySame(false, isset($missingBreadcrumb['page']['breadcrumb']), 'A dangling breadcrumb reference must not be added when BreadcrumbList was not generated.');

echo "Schema identity enricher tests passed.\n";
