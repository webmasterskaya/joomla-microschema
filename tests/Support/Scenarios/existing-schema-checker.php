<?php

declare(strict_types=1);

use Joomla\Component\Microschema\Administrator\Schema\ExistingSchemaChecker;

require_once __DIR__ . '/../../../com_microschema/administrator/src/Schema/ExistingSchemaChecker.php';

function assertExistingSchemaSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . PHP_EOL
            . 'Expected: ' . var_export($expected, true) . PHP_EOL
            . 'Actual: ' . var_export($actual, true),
        );
    }
}

$schemas = [
    'article.42' => ['@type' => 'Article', 'headline' => 'Generated article'],
    'breadcrumb' => ['@type' => 'BreadcrumbList', 'itemListElement' => []],
    'website' => ['@type' => 'WebSite', 'name' => 'Generated website'],
];
$messages = [];
$checker  = new ExistingSchemaChecker(
    static function (string $message) use (&$messages): void {
        $messages[] = $message;
    },
);

$jsonLd = <<<'HTML'
<script class="schema" type="application/ld+json">
{"@context":"https://schema.org","@graph":[{"@type":"Article"},{"@type":["Person","WebSite"]}]}
</script>
HTML;

assertExistingSchemaSame(
    ['breadcrumb' => $schemas['breadcrumb']],
    $checker->filter($jsonLd, $schemas),
    'Schema types found in Schema.org JSON-LD must suppress matching generated schemas.',
);
assertExistingSchemaSame(2, count($messages), 'Every skipped generated schema must be logged.');
assertExistingSchemaSame(
    true,
    str_contains($messages[0], 'matching JSON-LD already exists'),
    'The debug message must identify the detected markup format.',
);

$microdata = '<main itemscope itemtype="http://schema.org/BreadcrumbList"><span>Content</span></main>';
assertExistingSchemaSame(
    ['article.42' => $schemas['article.42'], 'website' => $schemas['website']],
    $checker->filter($microdata, $schemas),
    'Schema.org microdata must suppress the matching generated schema.',
);
assertExistingSchemaSame(
    $schemas,
    $checker->filter('<script type="application/ld+json">{"@context":"https://example.com","@type":"Article"}</script>', $schemas),
    'JSON-LD from another vocabulary must not suppress generated schemas.',
);
assertExistingSchemaSame(
    ['article.42' => $schemas['article.42'], 'breadcrumb' => $schemas['breadcrumb']],
    $checker->filter(
        '<script type="application/ld+json">'
        . '[{"@context":"https://example.com","@type":"Article"},'
        . '{"@context":"https://schema.org","@type":"WebSite"}]'
        . '</script>',
        $schemas,
    ),
    'A Schema.org context must apply only to its JSON-LD branch.',
);
assertExistingSchemaSame(
    $schemas,
    $checker->filter('<script type="application/ld+json">invalid</script>', $schemas),
    'Invalid JSON-LD must be ignored.',
);
assertExistingSchemaSame([], $checker->filter($jsonLd, []), 'An empty generated schema collection must stay empty.');

$lists = [
    'news' => ['@type' => 'ItemList', '@id' => 'https://example.test/blog/#news'],
    'popular' => ['@type' => 'ItemList', '@id' => 'https://example.test/blog/#popular'],
];
assertExistingSchemaSame($lists, $checker->filter('', $lists), 'Generated lists must coexist.');
$externalLists = '<script type="application/ld+json">{"@context":"https://schema.org","@graph":[{"@type":"ItemList","@id":"https://example.test/blog/#news"}]}</script>';
assertExistingSchemaSame(['popular' => $lists['popular']], $checker->filter($externalLists, $lists), 'Only a list with the same identifier must be filtered.');
assertExistingSchemaSame($lists, $checker->filter('<script type="application/ld+json">{"@context":"https://schema.org","@type":"ItemList"}</script>', $lists), 'Anonymous external lists must not suppress identified lists.');
assertExistingSchemaSame(['popular' => $lists['popular']], $checker->filter('<div itemscope itemid="https://example.test/blog/#news" itemtype="https://schema.org/ItemList"></div>', $lists), 'Microdata list identities must also be compared.');
assertExistingSchemaSame($lists, $checker->filter(str_replace('https://schema.org', 'https://other.example', $externalLists), $lists), 'Other vocabularies must not suppress lists.');

echo "Existing schema checker tests passed.\n";
