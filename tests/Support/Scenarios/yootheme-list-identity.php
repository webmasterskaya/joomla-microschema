<?php

declare(strict_types=1);

use Joomla\Plugin\System\MicroschemaYootheme\Builder\SchemaListIdentity;

define('_JEXEC', 1);
require_once __DIR__.'/../../../plg_system_microschemayootheme/src/Builder/SchemaListIdentity.php';

$page = 'https://example.test/blog/?start=20';
$identity = new SchemaListIdentity();
$first = new stdClass();
$second = new stdClass();
$actual = [
    $identity->resolve($first, '', $page),
    $identity->resolve($second, '', $page),
    $identity->resolve($first, '', $page),
    $identity->resolve($first, '#news', $page.'#old'),
    $identity->resolve($second, 'https://example.test/custom-list', $page),
];
if ($actual !== [$page.'#itemlist-1', $page.'#itemlist-2', $page.'#itemlist-1', $page.'#news', 'https://example.test/custom-list']) {
    throw new RuntimeException('List identity, fragments or repeated registration failed.');
}
$nextRequest = new SchemaListIdentity();
if ($nextRequest->resolve(new stdClass(), '', $page) !== $actual[0]
    || $nextRequest->resolve(new stdClass(), '', $page) !== $actual[1]) {
    throw new RuntimeException('Automatic IDs must not depend on PHP object IDs.');
}
echo "YOOtheme list identity tests passed.\n";
