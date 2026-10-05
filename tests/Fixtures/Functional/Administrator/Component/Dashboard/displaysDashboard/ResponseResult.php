<?php

declare(strict_types=1);

namespace Tests\Fixtures\Functional\Administrator\Component\Dashboard\displaysDashboard;

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
            contains: ['MicroSchema'],
            noSideEffects: true,
        );
    }
}
