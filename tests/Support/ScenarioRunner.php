<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\Assert;

final class ScenarioRunner
{
    public static function run(string $scenario): void
    {
        $path = __DIR__.'/Scenarios/'.$scenario.'.php';

        Assert::assertFileExists($path);

        $process = proc_open(
            [PHP_BINARY, $path],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            dirname(__DIR__, 2),
            null,
            ['bypass_shell' => true],
        );

        Assert::assertIsResource($process, sprintf('Unable to start scenario %s.', $scenario));

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        Assert::assertSame(
            0,
            proc_close($process),
            sprintf(
                "Scenario %s failed.\nSTDOUT:\n%s\nSTDERR:\n%s",
                $scenario,
                $stdout === false ? '' : $stdout,
                $stderr === false ? '' : $stderr,
            ),
        );
    }
}
