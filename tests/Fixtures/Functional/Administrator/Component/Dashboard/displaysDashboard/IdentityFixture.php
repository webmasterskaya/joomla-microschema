<?php

declare(strict_types=1);

namespace Tests\Fixtures\Functional\Administrator\Component\Dashboard\displaysDashboard;

use JoomlaCodeception\Contract\IdentityFixtureInterface;

final readonly class IdentityFixture implements IdentityFixtureInterface
{
    public function rootPermissions(): array
    {
        return ['core.login.admin' => 1];
    }

    public function componentPermissions(): array
    {
        return ['core.manage' => 1];
    }
}
