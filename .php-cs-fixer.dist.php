<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/public')
    ->exclude(['public/docs/assets', 'vendor', 'var', 'build'])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setLineEnding("\n")
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'single_quote' => true,
        'array_syntax' => ['syntax' => 'short'],
        'strict_param' => true,
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
        'blank_line_between_import_groups' => true,
        'binary_operator_spaces' => [
            'default' => 'single_space',
            'operators' => [
                '=>' => 'align_single_space_minimal',
                '='  => null,
            ],
        ],
    ])
    ->setFinder($finder);
