<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@PSR1' => true,
    'blank_line_after_namespace' => true,
    'constant_case' => true,
    'elseif' => true,
    'function_declaration' => true,
    'indentation_type' => true,
    'line_ending' => true,
    'lowercase_keywords' => true,
    'method_argument_space' => [
        'on_multiline' => 'ensure_fully_multiline',
    ],
    'no_break_comment' => true,
    'no_closing_tag' => true,
    'no_spaces_after_function_name' => true,
    'no_spaces_inside_parenthesis' => true,
    'no_trailing_whitespace' => true,
    'no_trailing_whitespace_in_comment' => true,
    'single_blank_line_at_eof' => true,
    'single_class_element_per_statement' => [
        'elements' => [
            'property',
        ],
    ],
    'single_import_per_statement' => true,
    'single_line_after_imports' => true,
    'switch_case_semicolon_to_colon' => true,
    'switch_case_space' => true,
    'blank_line_after_opening_tag' => true,
    'braces' => [
        'allow_single_line_anonymous_class_with_empty_body' => true,
    ],
    'class_definition' => ['space_before_parenthesis' => true], // defined in PSR12 ¶8. Anonymous Classes
    'compact_nullable_typehint' => true,
    'declare_equal_normalize' => true,
    'lowercase_cast' => true,
    'lowercase_static_reference' => true,
    'new_with_braces' => true,
    'no_blank_lines_after_class_opening' => true,
    'no_leading_import_slash' => true,
    'no_whitespace_in_blank_line' => true,
    'ordered_class_elements' => [
        'order' => [
            'use_trait',
        ],
    ],
    'ordered_imports' => [
        'imports_order' => [
            'class',
            'function',
            'const',
        ],
        'sort_algorithm' => 'none',
    ],
    'return_type_declaration' => true,
    'short_scalar_cast' => true,
    'single_blank_line_before_namespace' => true,
    'single_trait_insert_per_statement' => true,
    'ternary_operator_spaces' => true,
    'visibility_required' => true,
])
    ->setFinder($finder)
    ;
