<?php

declare(strict_types=1);

use Joomla\Component\Microschema\Administrator\Metadata\SocialMetadataInjector;

require_once __DIR__ . '/../../../com_microschema/administrator/src/Metadata/SocialMetadataInjector.php';

function assertSocialInjectorSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . PHP_EOL
            . 'Expected: ' . var_export($expected, true) . PHP_EOL
            . 'Actual: ' . var_export($actual, true),
        );
    }
}

$injector = new SocialMetadataInjector();
$html     = '<html><head><title>Page</title></head><body>Content</body></html>';
$tags     = [
    '<meta property="og:title" content="Title">',
    '<meta name="twitter:card" content="summary">',
];
$markup = implode(PHP_EOL, [
    '<!-- MicroSchema Social: start -->',
    ...$tags,
    '<!-- MicroSchema Social: end -->',
]);

assertSocialInjectorSame(
    '<html><head><title>Page</title>' . $markup . PHP_EOL . '</head><body>Content</body></html>',
    $injector->inject($html, $tags),
    'Social metadata must be inserted at the end of head.',
);
assertSocialInjectorSame($html, $injector->inject($html, []), 'An empty tag list must not change the document.');
assertSocialInjectorSame(
    '<html><body>Content</body></html>',
    $injector->inject('<html><body>Content</body></html>', $tags),
    'A missing head closing tag must not change the document.',
);

echo "Social metadata injector tests passed.\n";
