<?php

declare(strict_types=1);

namespace Tests\Fixtures\Functional\Site\Schema\ArticleSchema\doesNotRenderDisabledArticleSchema;

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
                'Functional Codeception disabled article',
            ],
            notContains: [
                'Functional disabled schema marker',
            ],
            noSideEffects: false,
            watchedTables: ['#__content', '#__microschema_items'],
        );
    }
}
