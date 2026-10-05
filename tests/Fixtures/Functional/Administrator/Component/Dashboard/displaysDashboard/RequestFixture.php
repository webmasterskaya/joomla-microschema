<?php

declare(strict_types=1);

namespace Tests\Fixtures\Functional\Administrator\Component\Dashboard\displaysDashboard;

use JoomlaCodeception\Contract\FixtureProviderInterface;
use JoomlaCodeception\Contract\RequestFixtureInterface;
use JoomlaCodeception\Dto\RedirectPolicy;
use JoomlaCodeception\Dto\RequestData;

final readonly class RequestFixture implements RequestFixtureInterface
{
    public function getRequest(FixtureProviderInterface $fixtureProvider): RequestData
    {
        return new RequestData(redirectPolicy: RedirectPolicy::Follow);
    }
}
