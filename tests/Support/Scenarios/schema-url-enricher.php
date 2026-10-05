<?php

declare(strict_types=1);

namespace Joomla\CMS\Router {
    final class Route
    {
        public const TLS_IGNORE = 0;

        public static function link($client, $url, $xhtml, $tls, $absolute): string
        {
            if ($client !== 'site' || $xhtml !== false || $absolute !== true || str_contains($url, '&amp;')) {
                throw new \RuntimeException('Wrong routing arguments.');
            }

            $fragment = parse_url($url, PHP_URL_FRAGMENT);

            return 'http://localhost:62080/site/blog/article'.($fragment !== null ? '#'.$fragment : '');
        }
    }
}

namespace {
    use Joomla\Component\Microschema\Administrator\Metadata\DescriptorInterface;
    use Joomla\Component\Microschema\Administrator\Metadata\Format;
    use Joomla\Component\Microschema\Administrator\Metadata\PropertyDefinition;
    use Joomla\Component\Microschema\Administrator\Schema\SchemaIdentityEnricher;
    use Joomla\Component\Microschema\Administrator\Schema\SchemaUrlEnricher;

    require_once __DIR__.'/../../../com_microschema/administrator/src/Metadata/Format.php';
    require_once __DIR__.'/../../../com_microschema/administrator/src/Metadata/PropertyDefinition.php';
    require_once __DIR__.'/../../../com_microschema/administrator/src/Metadata/DescriptorInterface.php';
    require_once __DIR__.'/../../../com_microschema/administrator/src/Schema/SchemaUrlEnricher.php';
    require_once __DIR__.'/../../../com_microschema/administrator/src/Schema/SchemaIdentityEnricher.php';

    abstract class TestSchemaUrlDescriptor implements DescriptorInterface
    {
        public function getFormat(): Format
        {
            return Format::JSON_LD;
        }
    }

    final class TestArticleUrlDescriptor extends TestSchemaUrlDescriptor
    {
        public function getName(): string
        {
            return 'Article';
        }

        public function getProperties(): array
        {
            return [
                new PropertyDefinition('headline'),
                new PropertyDefinition('image', ['URL', 'ImageObject'], multiple: true),
            ];
        }
    }

    final class TestBlogPostingUrlDescriptor extends TestSchemaUrlDescriptor
    {
        public function getName(): string
        {
            return 'BlogPosting';
        }

        public function getProperties(): array
        {
            return [
                new PropertyDefinition('url', ['URL']),
                new PropertyDefinition('image', ['URL', 'ImageObject'], multiple: true),
            ];
        }
    }

    final class TestListItemUrlDescriptor extends TestSchemaUrlDescriptor
    {
        public function getName(): string
        {
            return 'ListItem';
        }

        public function getProperties(): array
        {
            return [
                new PropertyDefinition('url', ['URL']),
                new PropertyDefinition('item', ['URL', 'BlogPosting']),
            ];
        }
    }

    final class TestCustomPluginUrlDescriptor extends TestSchemaUrlDescriptor
    {
        public function getName(): string
        {
            return 'CustomPluginType';
        }

        public function getProperties(): array
        {
            return [new PropertyDefinition('asset', ['URL'])];
        }
    }

    $descriptors = [
        'Article' => TestArticleUrlDescriptor::class,
        'BlogPosting' => TestBlogPostingUrlDescriptor::class,
        'ListItem' => TestListItemUrlDescriptor::class,
        'CustomPluginType' => TestCustomPluginUrlDescriptor::class,
    ];

    $enricher = new SchemaUrlEnricher();
    $cases = [
        'index.php?option=com_content&amp;id=1#article' => 'http://localhost:62080/site/blog/article#article',
        '/site/index.php?option=com_content&id=1' => 'http://localhost:62080/site/blog/article',
        'http://localhost:62080/site/index.php?id=1' => 'http://localhost:62080/site/blog/article',
        'images/a.jpg' => 'http://localhost:62080/site/images/a.jpg',
        '/images/a.jpg' => 'http://localhost:62080/images/a.jpg',
        '//cdn.example/a.jpg' => 'http://cdn.example/a.jpg',
        'https://external.example/index.php?id=1' => 'https://external.example/index.php?id=1',
        '#organization' => 'http://localhost:62080/site/#organization',
        'https://schema.org/ItemListOrderAscending' => 'https://schema.org/ItemListOrderAscending',
        '' => '',
    ];

    foreach ($cases as $input => $expected) {
        $actual = $enricher->enrich([
            'article' => [
                '@type' => 'Article',
                '@id' => $input,
                'image' => [$input],
                'headline' => $input,
            ],
        ], $descriptors, 'http://localhost:62080/site/')['article'];

        $expectedSchema = [
            '@type' => 'Article',
            '@id' => $expected,
            'image' => [$expected],
            'headline' => $input,
        ];

        if ($actual !== $expectedSchema) {
            throw new \RuntimeException('Failed URL case: '.$input);
        }
    }

    $custom = $enricher->enrich([
        'custom' => ['@type' => 'CustomPluginType', 'asset' => 'files/document.pdf'],
    ], $descriptors, 'http://localhost:62080/site/');

    if (($custom['custom']['asset'] ?? null) !== 'http://localhost:62080/site/files/document.pdf') {
        throw new \RuntimeException('A URL property registered by an integration descriptor must be normalized.');
    }

    $schema = $enricher->enrich([
        'list' => [
            '@type' => 'ItemList',
            'itemListElement' => [[
                '@type' => 'ListItem',
                'url' => 'index.php?id=1',
                'item' => [
                    '@type' => 'BlogPosting',
                    'url' => 'index.php?id=1',
                    'image' => ['images/a.jpg'],
                ],
            ]],
        ],
    ], $descriptors, 'http://localhost:62080/site/');
    $enriched = (new SchemaIdentityEnricher())->enrich(
        $schema,
        'http://localhost:62080/site/',
        'http://localhost:62080/site/',
    );
    $item = $enriched['list']['itemListElement'][0];

    if (
        $item['url'] !== $item['item']['url']
        || $item['item']['@id'] !== $item['url'].'#article'
        || $item['item']['image'] !== ['http://localhost:62080/site/images/a.jpg']
    ) {
        throw new \RuntimeException('Nested article URLs and generated identity must agree.');
    }

    echo "Schema URL enricher tests passed.\n";
}
