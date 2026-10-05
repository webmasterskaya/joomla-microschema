<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Support\ScenarioRunner;

final class JoomlaCategoryDataTypeTest extends TestCase
{
    public function testScenario(): void
    {
        ScenarioRunner::run('joomla-category-data-type');
    }
}
