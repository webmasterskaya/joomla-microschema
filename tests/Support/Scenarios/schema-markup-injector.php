<?php

declare(strict_types=1);

use Joomla\Component\Microschema\Administrator\Schema\SchemaMarkupInjector;

require_once __DIR__ . '/../../../com_microschema/administrator/src/Schema/SchemaMarkupInjector.php';

function assertMarkupSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . PHP_EOL
            . 'Expected: ' . var_export($expected, true) . PHP_EOL
            . 'Actual: ' . var_export($actual, true),
        );
    }
}

$injector = new SchemaMarkupInjector();
$tags     = [
    '<script type="application/ld+json">{"@type":"Article"}</script>',
    '<script type="application/ld+json">{"@type":"BreadcrumbList"}</script>',
];
$markup   = implode(PHP_EOL, [
    '<!-- MicroSchema: start -->',
    ...$tags,
    '<!-- MicroSchema: end -->',
]);
$html     = '<html><head><title>Page</title></head><body>Content</body></html>';

assertMarkupSame(
    '<html><head><title>Page</title>' . $markup . PHP_EOL . '</head><body>Content</body></html>',
    $injector->inject($html, $tags),
    'The default position must be at the end of head.',
);

assertMarkupSame(
    '<html><head><title>Page</title></head><body>Content' . $markup . PHP_EOL . '</body></html>',
    $injector->inject($html, $tags, SchemaMarkupInjector::POSITION_BODY),
    'The body position must be inserted at the end of body.',
);

assertMarkupSame($html, $injector->inject($html, []), 'An empty tag list must not change the document.');
assertMarkupSame(
    '<html><body>Content</body></html>',
    $injector->inject('<html><body>Content</body></html>', $tags),
    'A missing target closing tag must not change the document.',
);

echo "Schema markup injector tests passed.\n";
