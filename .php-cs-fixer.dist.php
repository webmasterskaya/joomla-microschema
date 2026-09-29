<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude([
        'dist',
        'docker',
        'node_modules',
        'tests',
        'vendor',
    ]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2x0' => true,
        '@PER-CS2x0:risky' => true,
        '@PHP8x4Migration' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => false,
        'modernize_strpos' => true,
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_imports' => true,
        'phpdoc_order' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'yoda_style' => false,
        'is_null' => false,
        'native_function_invocation' => false,
        'native_constant_invocation' => false,
        'phpdoc_to_comment' => false,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/.php-cs-fixer.cache');
