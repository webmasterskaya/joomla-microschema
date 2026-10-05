<?php

declare(strict_types=1);

use Joomla\Component\Microschema\Administrator\Metadata\ExistingSocialMetadataChecker;

require_once __DIR__ . '/../../../com_microschema/administrator/src/Metadata/ExistingSocialMetadataChecker.php';

function assertSocialMetadataSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . PHP_EOL
            . 'Expected: ' . var_export($expected, true) . PHP_EOL
            . 'Actual: ' . var_export($actual, true),
        );
    }
}

$tags = [
    '<meta property="og:title" content="Generated title">',
    '<meta content="one.jpg" property="og:image">',
    '<meta content="two.jpg" property="og:image">',
    '<meta name="twitter:card" content="summary_large_image">',
    '<meta property="fb:app_id" content="123">',
];
$body = <<<'HTML'
<html>
<head>
    <meta content="Existing title" PROPERTY='OG:TITLE'>
    <meta name=twitter:card content="summary">
    <meta name="description" content="Existing description">
</head>
<body></body>
</html>
HTML;
$messages = [];
$checker  = new ExistingSocialMetadataChecker(
    static function (string $message) use (&$messages): void {
        $messages[] = $message;
    },
);

assertSocialMetadataSame(
    [$tags[1], $tags[2], $tags[4]],
    $checker->filter($body, $tags),
    'Existing property and name metadata must suppress matching generated tags.',
);
assertSocialMetadataSame(2, count($messages), 'Every skipped metadata name must be logged once.');
assertSocialMetadataSame(
    true,
    str_contains($messages[0], 'matching meta tag already exists'),
    'The debug message must describe why generated metadata was skipped.',
);
$messages = [];
assertSocialMetadataSame(
    [$tags[0], $tags[3], $tags[4]],
    $checker->filter('<meta name="OG:IMAGE" content="existing.jpg">', $tags),
    'All generated tags with an existing repeatable metadata name must be skipped.',
);
assertSocialMetadataSame(1, count($messages), 'A repeatable metadata name must be logged only once.');
assertSocialMetadataSame(
    $tags,
    $checker->filter('<meta name="description" content="Existing description">', $tags),
    'Unrelated metadata must not suppress generated social tags.',
);
assertSocialMetadataSame([], $checker->filter($body, []), 'An empty generated tag list must stay empty.');
assertSocialMetadataSame($body, $body, 'Checking metadata must not modify the page body.');

echo "Existing social metadata checker tests passed.\n";
