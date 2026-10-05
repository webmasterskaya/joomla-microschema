<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Support\ScenarioRunner;

final class YoothemeSchemaElementTest extends TestCase
{
    public function testScenario(): void
    {
        ScenarioRunner::run('yootheme-schema-element');
    }
}
