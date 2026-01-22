<?php

return (new PhpCsFixer\Config())
    ->setRules([
        '@PHP82Migration' => true,
        '@PhpCsFixer' => true,
        '@Symfony' => true,
        'strict_param' => false,
        'class_attributes_separation' => ['elements' => ['const' => 'none', 'property' => 'none', 'method' => 'one', 'trait_import' => 'none']],
        'ordered_class_elements' => ['order' => ['use_trait', 'case', 'constant_public', 'constant_protected', 'constant_private', 'property_public', 'property_protected', 'property_private', 'construct', 'destruct', 'magic', 'phpunit', 'method_public', 'method_protected', 'method_private']],
        'concat_space' => ['spacing' => 'one'],
        'single_line_comment_style' => ['comment_types' => ['hash']],
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'method_argument_space' => [
            'keep_multiple_spaces_after_comma' => false,
            'on_multiline' => 'ensure_fully_multiline',
            'after_heredoc' => true,
        ],
        'single_line_throw' => false,
        'function_declaration' => [
            'closure_function_spacing' => 'none',
            'closure_fn_spacing' => 'none',
        ],
    ])
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->in([__DIR__ . '/src', __DIR__ . '/tests'])
            ->append([__FILE__])
    )
    ->setCacheFile('.php-cs-fixer.cache');
