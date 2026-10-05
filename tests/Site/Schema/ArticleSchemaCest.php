<?php

declare(strict_types=1);

namespace Tests\Site\Schema;

use Tests\Support\SiteTester;

final class ArticleSchemaCest
{
    public function rendersConfiguredArticleSchema(SiteTester $I): void
    {
        $I->loadDbFixtures();
        $I->openPageFromFixture('/index.php');
        $I->seeResponseCodeIs(200);
        $I->checkResponseResults();
    }

    public function doesNotRenderDisabledArticleSchema(SiteTester $I): void
    {
        $I->loadDbFixtures();
        $I->openPageFromFixture('/index.php');
        $I->seeResponseCodeIs(200);
        $I->checkResponseResults();
    }
}
