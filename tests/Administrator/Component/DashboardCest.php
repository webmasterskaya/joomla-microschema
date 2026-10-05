<?php

declare(strict_types=1);

namespace Tests\Administrator\Component;

use Tests\Support\AdministratorTester;

final class DashboardCest
{
    public function displaysDashboard(AdministratorTester $I): void
    {
        $I->loadDbFixtures();
        $I->loginFromFixture();
        $I->openPageFromFixture('/administrator/index.php?option=com_microschema');
        $I->seeResponseCodeIs(200);
        $I->checkResponseResults();
        $I->checkSideEffectResults();
    }
}
