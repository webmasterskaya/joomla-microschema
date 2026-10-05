<?php

declare(strict_types=1);

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;
use Joomla\Component\Microschema\Administrator\Metadata\DescriptorInterface;
use Joomla\Component\Microschema\Administrator\Metadata\Format;
use Joomla\Component\Microschema\Administrator\Metadata\PropertyDefinition;
use Joomla\Component\Microschema\Administrator\Metadata\Social\OpenGraph;
use Joomla\Component\Microschema\Administrator\Metadata\Social\TwitterCard;
use Joomla\Component\Microschema\Administrator\Metadata\SocialMetadataRenderer;

require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/Format.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/PropertyDefinition.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/DescriptorInterface.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/AbstractDescriptor.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/Social/OpenGraph.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/Social/TwitterCard.php';
require_once __DIR__ . '/../com_microschema/administrator/src/Metadata/SocialMetadataRenderer.php';

function assertSocialRendererSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . PHP_EOL
            . 'Expected: ' . var_export($expected, true) . PHP_EOL
            . 'Actual: ' . var_export($actual, true),
        );
    }
}

$renderer = new SocialMetadataRenderer();
$metadata = [
    'open-graph' => [
        '@type' => 'OpenGraph',
        'title' => 'Title & <Site>',
        'image' => ['one.jpg', 'two.jpg'],
    ],
    'twitter' => [
        '@type' => 'TwitterCard',
        'card' => 'summary',
        'title' => 'Twitter title',
    ],
    'unknown' => ['@type' => 'Unknown', 'title' => 'Unknown'],
];
$descriptors = [
    'OpenGraph' => OpenGraph::class,
    'TwitterCard' => TwitterCard::class,
];

assertSocialRendererSame(
    [
        '<meta property="og:title" content="Title &amp; &lt;Site&gt;">',
        '<meta property="og:image" content="one.jpg">',
        '<meta property="og:image" content="two.jpg">',
    ],
    $renderer->render($metadata, $descriptors, ['OpenGraph' => 1, 'TwitterCard' => 0]),
    'The renderer must output enabled metadata, repeatable values, and escaped attributes.',
);
assertSocialRendererSame(
    [
        '<meta name="twitter:card" content="summary">',
        '<meta name="twitter:title" content="Twitter title">',
    ],
    $renderer->render($metadata, $descriptors, ['OpenGraph' => 0, 'TwitterCard' => 1]),
    'Twitter Card metadata must use the name attribute.',
);
assertSocialRendererSame([], $renderer->render([], $descriptors, ['OpenGraph' => 1]), 'Empty metadata must render no tags.');

echo "Social metadata renderer tests passed.\n";
