<?php

declare(strict_types=1);

use Joomla\Component\Microschema\Administrator\Schema\JsonLdRenderer;

require_once __DIR__ . '/../../../com_microschema/administrator/src/Schema/JsonLdRenderer.php';

function assertJsonLd(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$renderer = new JsonLdRenderer();
$tags     = $renderer->render([
    'primary' => [
        '@type' => 'Product',
        'name' => 'Товар </script><script>alert("x")</script>',
    ],
    'breadcrumb' => [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
    ],
    'custom' => [
        '@context' => 'https://example.test/context',
        '@type' => 'CustomType',
    ],
]);

assertJsonLd(count($tags) === 1, 'All resolved schemas must share one JSON-LD tag.');
assertJsonLd(str_contains($tags[0], '"@context":"https://schema.org","@graph":['), 'The tag must expose a shared Schema.org context and graph.');
assertJsonLd(str_contains($tags[0], 'Товар'), 'Unicode values must remain readable.');
assertJsonLd(!str_contains($tags[0], '</script><script>'), 'A value must not be able to close the JSON-LD script tag.');
assertJsonLd(str_contains($tags[0], '\\u003C/script\\u003E'), 'HTML tag delimiters must be hex encoded.');
assertJsonLd(substr_count($tags[0], 'https://schema.org') === 1, 'The shared Schema.org context must not be repeated in graph nodes.');
assertJsonLd(str_contains($tags[0], '"@context":"https://example.test/context"'), 'A custom node context must be preserved.');
assertJsonLd(
    strpos($tags[0], '"@type":"Product"') < strpos($tags[0], '"@type":"BreadcrumbList"'),
    'Graph nodes must preserve resolved schema order.',
);
assertJsonLd($renderer->render([]) === [], 'An empty schema collection must produce no tags.');

$recursive         = [];
$recursive['self'] = &$recursive;
$exceptionThrown   = false;

try {
    $renderer->render(['recursive' => $recursive]);
} catch (JsonException) {
    $exceptionThrown = true;
}

assertJsonLd($exceptionThrown, 'Invalid recursive data must raise a JSON exception.');

echo "JSON-LD renderer tests passed.\n";
