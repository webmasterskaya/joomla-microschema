<?php

declare(strict_types=1);

namespace Tests\Fixtures\Functional\Site\Schema\ArticleSchema\rendersConfiguredArticleSchema;

use JoomlaCodeception\Contract\FixtureProviderInterface;
use JoomlaCodeception\Contract\RequestFixtureInterface;
use JoomlaCodeception\Dto\RedirectPolicy;
use JoomlaCodeception\Dto\RequestData;

final readonly class RequestFixture implements RequestFixtureInterface
{
    public function getRequest(FixtureProviderInterface $fixtureProvider): RequestData
    {
        return new RequestData(
            fields: [
                'option' => 'com_content',
                'view' => 'article',
                'id' => $fixtureProvider->reference(DatabaseFixture::class, 'article'),
            ],
            redirectPolicy: RedirectPolicy::Follow,
        );
    }
}
