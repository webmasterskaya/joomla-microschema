<?php

declare(strict_types=1);

use Joomla\Component\Microschema\Administrator\Schema\SchemaCollector;
use Joomla\Component\Microschema\Administrator\Schema\SchemaResolver;

require_once __DIR__ . '/../../../com_microschema/administrator/src/Schema/ContextNode.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/Schema/ContextPath.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/Schema/SchemaCandidate.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/Schema/SchemaRegistry.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/Schema/SchemaCollector.php';
require_once __DIR__ . '/../../../com_microschema/administrator/src/Schema/SchemaResolver.php';

function assertCollectorSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . PHP_EOL
            . 'Expected: ' . var_export($expected, true) . PHP_EOL
            . 'Actual: ' . var_export($actual, true),
        );
    }
}

$collector = new SchemaCollector();
$resolver  = new SchemaResolver();

// Arbitrary PHP code can add output without knowing the active component context.
$collector->addSchema('module.12', [
    '@type' => 'Organization',
    'name'  => 'Module schema',
]);

$initial = $resolver->resolve($collector->getPath(), $collector->getRegistry());

assertCollectorSame('Module schema', $initial['module.12']['name'], 'A root schema must be resolvable immediately.');
assertCollectorSame(
    SchemaCollector::ROOT_KEY,
    $collector->getPath()->all()[0]->getKey(),
    'The collector must start with its root page context.',
);

// A candidate may be registered before an integration adds its context to the active path.
$collector->addSchema(
    'primary',
    ['@type' => 'Article', 'name' => 'Template schema'],
    contextKey: 'com_content.article:42',
);
$article = $collector->appendContext('com_content.article', 42);

$afterTemplate = $resolver->resolve($collector->getPath(), $collector->getRegistry());

assertCollectorSame('Template schema', $afterTemplate['primary']['name'], 'A late context must resolve an earlier candidate.');

// Event-style collection and arbitrary PHP share the same state and context precedence.
$collector->addSchema('primary', ['name' => 'Root fallback']);
$collector->addSchema('primary', ['name' => 'Integration schema'], contextKey: $article->getKey());
$combined = $resolver->resolve($collector->getPath(), $collector->getRegistry());

assertCollectorSame('Integration schema', $combined['primary']['name'], 'A specific context must override the root candidate.');
assertCollectorSame('Module schema', $combined['module.12']['name'], 'An unrelated root UID must remain in the result.');

$collector->addSocialMetadata('social.page', [
    '@type' => 'OpenGraph',
    'title' => 'Root social title',
]);
$collector->addSocialMetadata(
    'social.page',
    ['title' => 'Article social title'],
    contextKey: $article->getKey(),
);
$social = $resolver->resolve($collector->getPath(), $collector->getSocialRegistry());

assertCollectorSame(
    'Article social title',
    $social['social.page']['title'],
    'Social metadata must use the shared context path and specificity rules.',
);
assertCollectorSame(
    false,
    isset($combined['social.page']),
    'Social metadata candidates must not leak into the Schema.org registry.',
);

echo "Schema collector tests passed.\n";
