<?php
/**
 * SPDX-FileCopyrightText: 2010 - 2024 the Friendica project
 *
 * SPDX-License-Identifier: CC0-1.0
 */

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
	->in(__DIR__)
	->notPath('addon')
	->notPath('bin/dev')
	->notPath('config')
	->notPath('doc')
	->notPath('images')
	->notPath('mods')
	->notPath('spec')
	->notPath('vendor')
	->notPath('view/asset')
	->notPath('lang')
	->notPath('view/smarty3/compiled');

$config = new PhpCsFixer\Config();
return $config
	->setRules([
		'@PSR1'                   => true,
		'@PSR2'                   => true,
		'@PSR12'                  => true,
		'align_multiline_comment' => true,
		'array_indentation'       => true,
		'array_syntax'            => [
			'syntax' => 'short',
		],
		'binary_operator_spaces' => [
			'default'   => 'single_space',
			'operators' => [
				'=>' => 'align_single_space_minimal',
				'='  => 'align_single_space_minimal',
				'??' => 'align_single_space_minimal',
			],
		],
		'blank_line_after_namespace'   => true,
		'braces'                       => [
			'position_after_anonymous_constructs'         => 'same',
			'position_after_control_structures'           => 'same',
			'position_after_functions_and_oop_constructs' => 'next',
		],
		'elseif'               => true,
		'encoding'             => true,
		'full_opening_tag'     => true,
		'function_declaration' => [
			'closure_function_spacing' => 'one',
		],
		'indentation_type' => true,
		'line_ending'      => true,
		'list_syntax'      => [
			'syntax' => 'long',
		],
		'lowercase_keywords'                 => true,
		'method_argument_space'              => [],
		'no_closing_tag'                     => true,
		'no_spaces_after_function_name'      => true,
		'no_spaces_inside_parenthesis'       => true,
		'no_trailing_whitespace'             => true,
		'no_trailing_whitespace_in_comment'  => true,
		'no_unused_imports'                  => true,
		'single_blank_line_at_eof'           => true,
		'single_class_element_per_statement' => true,
		'single_import_per_statement'        => true,
		'single_line_after_imports'          => true,
		'switch_case_space'                  => true,
		'ternary_operator_spaces'            => false,
		'visibility_required'                => [
			'elements' => ['property', 'method']
		],
		'new_with_braces' => true,
	])
	->setFinder($finder)
	->setIndent("\t");
