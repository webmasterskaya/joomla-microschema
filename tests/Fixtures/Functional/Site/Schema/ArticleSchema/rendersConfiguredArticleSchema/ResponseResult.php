<?php

declare(strict_types=1);

namespace Tests\Fixtures\Functional\Site\Schema\ArticleSchema\rendersConfiguredArticleSchema;

use JoomlaCodeception\Contract\FixtureProviderInterface;
use JoomlaCodeception\Contract\ResponseResultInterface;
use JoomlaCodeception\Dto\ResponseExpectation;

final readonly class ResponseResult implements ResponseResultInterface
{
    public function response(FixtureProviderInterface $fixtureProvider): ResponseExpectation
    {
        return new ResponseExpectation(
            contentTypeContains: 'text/html',
            loginForm: false,
            contains: [
                'Functional Codeception schema article',
                '"@type":"Article"',
                'Functional Codeception headline',
            ],
            notContains: [],
            noSideEffects: false,
            watchedTables: ['#__content', '#__microschema_items'],
        );
    }
}
