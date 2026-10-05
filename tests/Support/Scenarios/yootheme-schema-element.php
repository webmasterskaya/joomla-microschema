<?php

declare(strict_types=1);

use Joomla\Plugin\System\MicroschemaYootheme\Builder\SchemaElementPayloadBuilder;
use Joomla\Plugin\System\MicroschemaYootheme\Builder\SchemaElementFieldBuilder;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\Article;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\Organization;
use Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg\ItemList;

define('_JEXEC', 1);

if (!class_exists('Joomla\\CMS\\Language\\Text')) {
    final class YoothemeSchemaTextStub
    {
        public static function _(string $key): string
        {
            return $key;
        }

        public static function sprintf(string $key, mixed ...$arguments): string
        {
            return $key.($arguments === [] ? '' : ': '.implode(', ', $arguments));
        }
    }

    class_alias(YoothemeSchemaTextStub::class, 'Joomla\\CMS\\Language\\Text');
}

require_once __DIR__.'/../../../com_microschema/administrator/src/Schema/SchemaDataBuilder.php';
require_once __DIR__.'/../../../com_microschema/administrator/src/Metadata/Format.php';
require_once __DIR__.'/../../../com_microschema/administrator/src/Metadata/PropertyDefinition.php';
require_once __DIR__.'/../../../com_microschema/administrator/src/Metadata/DescriptorInterface.php';
require_once __DIR__.'/../../../com_microschema/administrator/src/Metadata/AbstractDescriptor.php';
require_once __DIR__.'/../../../com_microschema/administrator/src/Metadata/SchemaOrg/Article.php';
require_once __DIR__.'/../../../com_microschema/administrator/src/Metadata/SchemaOrg/Organization.php';
require_once __DIR__.'/../../../com_microschema/administrator/src/Metadata/SchemaOrg/ItemList.php';
require_once __DIR__.'/../../../plg_system_microschemayootheme/src/Builder/SchemaElementPayloadBuilder.php';
require_once __DIR__.'/../../../plg_system_microschemayootheme/src/Builder/SchemaElementFieldBuilder.php';

function assertYoothemeSchemaSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message.PHP_EOL
            .'Expected: '.var_export($expected, true).PHP_EOL
            .'Actual: '.var_export($actual, true),
        );
    }
}

$builder = new SchemaElementPayloadBuilder();
$types = ['Article', 'Organization'];
$fieldBuilder = new SchemaElementFieldBuilder([
    'Article' => Article::class,
    'Organization' => Organization::class,
    'ItemList' => ItemList::class,
], ['ItemList']);
$fieldConfiguration = $fieldBuilder->build();
$headlineField = $fieldBuilder->getFieldName('Article', 'headline');
$keywordsField = $fieldBuilder->getFieldName('Article', 'keywords');
$freeField = $fieldBuilder->getFieldName('Article', 'isAccessibleForFree');

assertYoothemeSchemaSame(
    true,
    $fieldConfiguration['fields'][$headlineField]['source'] ?? null,
    'Every generated Schema.org property field must support dynamic content.',
);
assertYoothemeSchemaSame(
    'schema_type=="Article"',
    $fieldConfiguration['fields'][$headlineField]['show'] ?? null,
    'Generated fields must only be shown for their Schema.org type.',
);
assertYoothemeSchemaSame(
    false,
    isset($fieldConfiguration['fields'][$fieldBuilder->getFieldName('Article', 'inLanguage')]),
    'Automatically populated Schema.org properties must not be editable.',
);
assertYoothemeSchemaSame(
    false,
    isset($fieldConfiguration['fields'][$fieldBuilder->getFieldName('ItemList', 'name')]),
    'List schema fields must be excluded from the single-schema element.',
);
assertYoothemeSchemaSame(
    false,
    in_array($fieldBuilder->getFieldName('Article', 'author'), $fieldConfiguration['names'], true),
    'Properties with a value type selector must not remain in the main fieldset.',
);
$objectGroups = array_column($fieldConfiguration['groups'], null, 'label');
assertYoothemeSchemaSame(
    [
        $fieldBuilder->getFieldName('Article', 'author').'__mode',
        $fieldBuilder->getFieldName('Article', 'author'),
        $fieldBuilder->getFieldName('Article', 'author').'__id',
        $fieldBuilder->getFieldName('Article', 'author').'__name',
        $fieldBuilder->getFieldName('Article', 'author').'__url',
        $fieldBuilder->getFieldName('Article', 'author').'__image',
    ],
    $objectGroups['author']['fields'] ?? null,
    'The author type selector and its dependent inputs must have a separate visual group.',
);
assertYoothemeSchemaSame(
    'schema_type=="Article"',
    $objectGroups['author']['show'] ?? null,
    'A generated object group must follow the schema type selector.',
);
assertYoothemeSchemaSame(
    ['type' => 'group', 'divider' => true],
    array_intersect_key($objectGroups['author'] ?? [], ['type' => true, 'divider' => true]),
    'A generated object section must use the YOOtheme visual group syntax.',
);
assertYoothemeSchemaSame(
    'select',
    $fieldConfiguration['fields'][$freeField]['type'] ?? null,
    'Boolean properties must use a select so an unset value is distinct from false.',
);
assertYoothemeSchemaSame(
    ['JNONE' => '', 'JYES' => '1', 'JNO' => '0'],
    $fieldConfiguration['fields'][$freeField]['options'] ?? null,
    'Boolean properties must provide unset, true, and false options.',
);

assertYoothemeSchemaSame(
    [],
    $fieldBuilder->extractProperties('Article', [$freeField => '']),
    'An unset boolean property must be omitted.',
);
assertYoothemeSchemaSame(
    ['isAccessibleForFree' => true],
    $fieldBuilder->extractProperties('Article', [$freeField => '1']),
    'An explicitly true boolean property must be preserved.',
);

$extractedProperties = $fieldBuilder->extractProperties('Article', [
    $headlineField => 'Builder article',
    $keywordsField => "schema.org\nYOOtheme\n",
    $freeField => '0',
]);

assertYoothemeSchemaSame(
    [
        'headline' => 'Builder article',
        'keywords' => ['schema.org', 'YOOtheme'],
        'isAccessibleForFree' => false,
    ],
    $extractedProperties,
    'Generated fields must be converted into Schema.org properties.',
);
assertYoothemeSchemaSame(
    true,
    $fieldBuilder->hasSubmittedFields('Article', [$headlineField => '']),
    'An explicitly cleared generated field must prevent legacy JSON from being restored.',
);

$nestedFieldBuilder = new SchemaElementFieldBuilder(
    ['Article' => Article::class],
    [],
    'item_type',
    'item_schema',
);
$nestedConfiguration = $nestedFieldBuilder->build();
$nestedHeadlineField = $nestedFieldBuilder->getFieldName('Article', 'headline');

assertYoothemeSchemaSame(
    'item_schema_article_headline',
    $nestedHeadlineField,
    'Generated fields must support a custom property prefix.',
);
assertYoothemeSchemaSame(
    'item_type=="Article"',
    $nestedConfiguration['fields'][$nestedHeadlineField]['show'] ?? null,
    'Generated fields must support a custom type selector.',
);
$nestedGroups = array_column($nestedConfiguration['groups'], null, 'label');
assertYoothemeSchemaSame(
    'item_type=="Article"',
    $nestedGroups['author']['show'] ?? null,
    'Nested object groups must follow the nested item type selector.',
);

assertYoothemeSchemaSame(
    [
        '@type' => 'Article',
        'headline' => 'Builder article',
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Publisher',
        ],
        'commentCount' => 0,
    ],
    $builder->build('Article', json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WrongType',
        'headline' => 'Builder article',
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Publisher',
        ],
        'commentCount' => 0,
    ], JSON_THROW_ON_ERROR), $types),
    'The selected type must be authoritative and JSON properties must be preserved.',
);

assertYoothemeSchemaSame(
    [],
    $builder->build('UnknownType', '{"name":"Ignored"}', $types),
    'An unregistered Schema.org type must not be rendered.',
);

assertYoothemeSchemaSame(
    [],
    $builder->build('Article', '{}', $types),
    'A schema without properties must not be rendered.',
);

$invalidJsonRejected = false;

try {
    $builder->build('Article', '{invalid', $types);
} catch (InvalidArgumentException) {
    $invalidJsonRejected = true;
}

assertYoothemeSchemaSame(true, $invalidJsonRejected, 'Invalid JSON must be rejected.');

$authorField = $fieldBuilder->getFieldName('Article', 'author');
$publisherField = $fieldBuilder->getFieldName('Article', 'publisher');
$imageField = $fieldBuilder->getFieldName('Article', 'image');
$mainEntityField = $fieldBuilder->getFieldName('Article', 'mainEntityOfPage');
assertYoothemeSchemaSame('text', $fieldConfiguration['fields'][$authorField]['type'] ?? null, 'Manual author references must use a single-line input.');
assertYoothemeSchemaSame('text', $fieldConfiguration['fields'][$publisherField]['type'] ?? null, 'Manual publisher references must use a single-line input.');
assertYoothemeSchemaSame('link', $fieldConfiguration['fields'][$mainEntityField]['type'] ?? null, 'The default mainEntityOfPage value must use a URL input.');
assertYoothemeSchemaSame(
    ['PLG_SYSTEM_MICROSCHEMAYOOTHEME_VALUE_URL' => '', 'PLG_SYSTEM_MICROSCHEMAYOOTHEME_VALUE_REFERENCE' => '@id', 'WebPage' => 'WebPage'],
    $fieldConfiguration['fields'][$mainEntityField.'__mode']['options'] ?? null,
    'mainEntityOfPage must allow URL, @id, and WebPage modes.',
);
assertYoothemeSchemaSame(
    ['author' => [['@id' => '#organisation'], ['@id' => 'https://example.test/person']], 'publisher' => ['@id' => '#publisher']],
    $fieldBuilder->extractProperties('Article', [$authorField => "#organisation\nhttps://example.test/person", $publisherField => '#publisher']),
    'Legacy references must become objects without renaming identifiers.',
);
$objectProps = [
    $authorField.'__mode' => 'Person',
    $authorField.'__name' => 'Alice',
    $authorField => '#ignored-old-reference',
    $publisherField.'__mode' => 'Organization',
    $publisherField.'__id' => '#publisher',
    $publisherField.'__name' => 'Publisher',
    $imageField.'__mode' => 'ImageObject',
    $imageField.'__url' => 'images/photo.jpg',
    $imageField.'__caption' => 'Photo',
    $imageField.'__width' => '1200',
    $imageField.'__height' => '-1',
];
$expectedObjects = [
    'image' => [['@type' => 'ImageObject', 'url' => 'images/photo.jpg', 'caption' => 'Photo', 'width' => 1200]],
    'author' => [['@type' => 'Person', 'name' => 'Alice']],
    'publisher' => ['@type' => 'Organization', '@id' => '#publisher', 'name' => 'Publisher'],
];
assertYoothemeSchemaSame($expectedObjects, $fieldBuilder->extractProperties('Article', $objectProps), 'Object mode must use individual fields and ignore stale hidden values.');
assertYoothemeSchemaSame(true, $fieldBuilder->hasSubmittedFields('Article', [$authorField.'__name' => 'Alice']), 'Object-only fields must count as submitted.');
assertYoothemeSchemaSame([], $fieldBuilder->extractProperties('Article', [$authorField.'__mode' => 'Person', $authorField => '#stale']), 'Empty objects must not restore hidden references.');
assertYoothemeSchemaSame(['image' => [['@id' => '#photo']]], $fieldBuilder->extractProperties('Article', [$imageField.'__mode' => '@id', $imageField.'__id' => '#photo']), 'Images must support references.');
assertYoothemeSchemaSame(['mainEntityOfPage' => 'https://example.test/article'], $fieldBuilder->extractProperties('Article', [$mainEntityField => 'https://example.test/article']), 'The default mainEntityOfPage mode must preserve a URL scalar.');
assertYoothemeSchemaSame(['mainEntityOfPage' => ['@id' => '#webpage']], $fieldBuilder->extractProperties('Article', [$mainEntityField.'__mode' => '@id', $mainEntityField.'__id' => '#webpage']), 'mainEntityOfPage must support an entity reference.');
assertYoothemeSchemaSame(
    ['mainEntityOfPage' => ['@type' => 'WebPage', '@id' => '#webpage', 'name' => 'Article page', 'url' => 'https://example.test/article']],
    $fieldBuilder->extractProperties('Article', [
        $mainEntityField.'__mode' => 'WebPage',
        $mainEntityField.'__id' => '#webpage',
        $mainEntityField.'__name' => 'Article page',
        $mainEntityField.'__url' => 'https://example.test/article',
    ]),
    'mainEntityOfPage must build a WebPage object from individual fields.',
);
assertYoothemeSchemaSame(['author' => [['@type' => 'Person', 'name' => 'Existing']]], $fieldBuilder->extractProperties('Article', [$authorField => ['@type' => 'Person', 'name' => 'Existing']]), 'Existing object values must survive.');
assertYoothemeSchemaSame('image', $fieldConfiguration['fields'][$imageField]['type'], 'Default image input must use the media picker.');
foreach ([$authorField.'__name', $publisherField.'__url', $imageField.'__width'] as $fieldName) {
    assertYoothemeSchemaSame(true, $fieldConfiguration['fields'][$fieldName]['source'], 'Object values must allow dynamic content.');
}
$nestedProps = [];
foreach ($objectProps as $key => $value) {
    $nestedProps['item_'.$key] = $value;
}
assertYoothemeSchemaSame($expectedObjects, $nestedFieldBuilder->extractProperties('Article', $nestedProps), 'Nested list objects must use the same field extraction.');
assertYoothemeSchemaSame(['@type' => 'Article'] + $expectedObjects, $builder->build('Article', $expectedObjects, $types), 'Objects must survive payload construction.');

echo "YOOtheme schema element tests passed.\n";
