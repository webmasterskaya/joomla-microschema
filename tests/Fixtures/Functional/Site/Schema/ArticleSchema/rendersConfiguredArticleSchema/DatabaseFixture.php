<?php

declare(strict_types=1);

namespace Tests\Fixtures\Functional\Site\Schema\ArticleSchema\rendersConfiguredArticleSchema;

use JoomlaCodeception\Contract\FixtureInterface;
use JoomlaCodeception\Contract\FixtureProviderInterface;
use JoomlaCodeception\Dto\DatabaseFixture as DatabaseRow;

final readonly class DatabaseFixture implements FixtureInterface
{
    public function getFixturesData(FixtureProviderInterface $fixtureProvider): array
    {
        return [
            new DatabaseRow('article', '#__content', [
                'title' => 'Functional Codeception schema article',
                'alias' => 'functional-codeception-schema-article',
                'introtext' => '<p>Functional Codeception schema article</p>',
                'fulltext' => '',
                'state' => 1,
                'catid' => 2,
                'created' => '2026-01-01 00:00:00',
                'created_by' => 0,
                'modified' => '2026-01-01 00:00:00',
                'modified_by' => 0,
                'access' => 1,
                'language' => '*',
                'images' => '{}',
                'urls' => '{}',
                'attribs' => '{}',
                'metakey' => '',
                'metadesc' => '',
                'metadata' => '{}',
            ]),
            new DatabaseRow('settings', '#__microschema_items', [
                'context' => 'com_content.article',
                'item_id' => $fixtureProvider->reference(self::class, 'article'),
                'params' => json_encode([
                    'schema_type' => 'Article',
                    'schema_properties' => ['headline' => 'Functional Codeception headline'],
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'state' => 1,
            ]),
        ];
    }
}
