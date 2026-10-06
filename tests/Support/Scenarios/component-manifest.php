<?php

declare(strict_types=1);

function assertComponentManifestSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . PHP_EOL
            . 'Expected: ' . var_export($expected, true) . PHP_EOL
            . 'Actual: ' . var_export($actual, true),
        );
    }
}

$componentRoot = __DIR__ . '/../../../com_microschema';
$manifest = simplexml_load_file($componentRoot . '/microschema.xml');

if (!$manifest instanceof SimpleXMLElement) {
    throw new RuntimeException('Unable to load the component manifest.');
}

foreach (['install', 'uninstall'] as $operation) {
    $files = $manifest->{$operation}->sql->file ?? null;

    if (!$files instanceof SimpleXMLElement || count($files) !== 1) {
        throw new RuntimeException(sprintf('The component %s SQL declaration is missing.', $operation));
    }

    $file = $files[0];
    $relativePath = trim((string) $file);

    assertComponentManifestSame('mysql', (string) $file['driver'], sprintf('The %s SQL driver must target MySQL.', $operation));
    assertComponentManifestSame(
        'utf8',
        (string) $file['charset'],
        sprintf('The %s SQL charset selector must use the value supported by the Joomla installer.', $operation),
    );
    assertComponentManifestSame(
        true,
        is_file($componentRoot . '/administrator/' . $relativePath),
        sprintf('The declared %s SQL file must exist in the administrator component.', $operation),
    );
}

$schemaPaths = $manifest->update->schemas->schemapath ?? null;

if (!$schemaPaths instanceof SimpleXMLElement || count($schemaPaths) !== 1) {
    throw new RuntimeException('The component update schema declaration is missing.');
}

$schemaPath = $schemaPaths[0];
assertComponentManifestSame('mysql', (string) $schemaPath['type'], 'The update schema must target MySQL.');
assertComponentManifestSame(
    true,
    is_dir($componentRoot . '/administrator/' . trim((string) $schemaPath)),
    'The declared update schema directory must exist in the administrator component.',
);

$installSql = file_get_contents($componentRoot . '/administrator/sql/install.mysql.utf8mb4.sql');
$updateSql = file_get_contents($componentRoot . '/administrator/sql/updates/mysql/0.1.1.sql');

assertComponentManifestSame(true, is_string($installSql), 'The component install SQL must be readable.');
assertComponentManifestSame(true, is_string($updateSql), 'The component update SQL must be readable.');
assertComponentManifestSame(
    true,
    str_contains($installSql, 'CREATE TABLE IF NOT EXISTS `#__microschema_items`'),
    'A clean installation must create the MicroSchema items table.',
);
assertComponentManifestSame(
    true,
    str_contains($updateSql, 'CREATE TABLE IF NOT EXISTS `#__microschema_items`'),
    'Database repair and upgrades must create the MicroSchema items table.',
);
assertComponentManifestSame(
    true,
    str_contains($installSql, 'DEFAULT CHARSET=utf8mb4'),
    'The installer selector must not change the table character set.',
);

echo "Component manifest tests passed.\n";
