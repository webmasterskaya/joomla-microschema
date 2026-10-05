<?php

declare(strict_types=1);

use Joomla\Plugin\System\Microschema\Extension\Microschema;

require_once __DIR__ . '/system-plugin-bootstrap.php';

function assertGlobalBreadcrumbSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
}

$reflection = new ReflectionClass(Microschema::class);
/** @var Microschema $plugin */
$plugin = $reflection->newInstanceWithoutConstructor();
$isHomePage = $reflection->getMethod('isHomePage');

assertGlobalBreadcrumbSame(
    true,
    $isHomePage->invoke($plugin, (object) ['id' => 101], (object) ['id' => 101]),
    'BreadcrumbList must not be generated for the home menu item.',
);
assertGlobalBreadcrumbSame(
    false,
    $isHomePage->invoke($plugin, (object) ['id' => 101], (object) ['id' => 202]),
    'BreadcrumbList must remain enabled for an inner menu item.',
);
assertGlobalBreadcrumbSame(
    false,
    $isHomePage->invoke($plugin, null, (object) ['id' => 101]),
    'A missing default menu item must not be treated as the home page.',
);

echo "Global breadcrumb tests passed.\n";
