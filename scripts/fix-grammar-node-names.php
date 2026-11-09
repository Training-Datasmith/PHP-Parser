<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$nodeRoot = $root . '/lib/PhpParser/Node';
$grammar = $root . '/grammar/php.y';

/**
 * @return list<string>
 */
function compress_segments(string $grammarName): string
{
    $parts = explode('\\', $grammarName);
    foreach ($parts as &$part) {
        $part = str_replace('_', '', $part);
    }

    return implode('\\', $parts);
}

/** @var array<string, string> */
$map = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($nodeRoot, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $fileInfo) {
    if (substr($fileInfo->getFilename(), -4) !== '.php') {
        continue;
    }
    $rel = substr($fileInfo->getPathname(), strlen($nodeRoot) + 1);
    $rel = substr($rel, 0, -4);
    $grammarName = str_replace('/', '\\', $rel);
    $compressed = compress_segments($grammarName);
    if ($compressed !== $grammarName) {
        $map[$compressed] = $grammarName;
    }
}

uksort($map, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

$manual = [
    'Stmt\\ElseIf' => 'Stmt\\Else_If_',
    'Stmt\\InlineHTML' => 'Stmt\\Inline_Html',
    'Scalar\\MagicConst' => 'Scalar\\Magic_Const',
    'Node\\InterpolatedStringPart' => 'Node\\Interpolated_String_Part',
    'Scalar\\InterpolatedString' => 'Scalar\\Interpolated_String',
];

foreach ($manual as $from => $to) {
    $map[$from] = $to;
}

uksort($map, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

$content = file_get_contents($grammar);
foreach ($map as $from => $to) {
    if ($from === $to) {
        continue;
    }
    $pattern = '#' . preg_quote($from, '#') . '(?=[\[\s;,()]|$)#';
    $content = preg_replace($pattern, $to, $content);
}

$methodMap = [
    'handleNamespaces' => 'handle_namespaces',
    'emitError' => 'emit_error',
    'handleHaltCompiler' => 'handle_halt_compiler',
    'checkNamespace' => 'check_namespace',
    'checkConstantAttributes' => 'check_constant_attributes',
    'checkUseUse' => 'check_use_use',
    'inlineHtmlHasLeadingNewline' => 'inline_html_has_leading_newline',
    'checkTryCatch' => 'check_try_catch',
    'checkClass' => 'check_class',
    'checkInterface' => 'check_interface',
    'checkEnum' => 'check_enum',
    'checkClassModifier' => 'check_class_modifier',
    'fixupAlternativeElse' => 'fixup_alternative_else',
    'fixupArrayDestructuring' => 'fixup_array_destructuring',
    'checkModifier' => 'check_modifier',
    'handleBuiltinTypes' => 'handle_builtin_types',
    'checkPropertyHooksForMultiProperty' => 'check_property_hooks_for_multi_property',
    'checkEmptyPropertyHookList' => 'check_empty_property_hook_list',
    'addPropertyNameToHooks' => 'add_property_name_to_hooks',
    'checkClassConst' => 'check_class_const',
    'checkClassMethod' => 'check_class_method',
    'checkPropertyHook' => 'check_property_hook',
    'checkPropertyHookModifiers' => 'check_property_hook_modifiers',
    'checkPipeOperatorParentheses' => 'check_pipe_operator_parentheses',
    'checkParam' => 'check_param',
    'parseLNumber' => 'parse_l_number',
    'parseNumString' => 'parse_num_string',
    'parseDocString' => 'parse_doc_string',
    'getIntCastKind' => 'get_int_cast_kind',
    'getFloatCastKind' => 'get_float_cast_kind',
    'getStringCastKind' => 'get_string_cast_kind',
    'getBoolCastKind' => 'get_bool_cast_kind',
    'createExitExpr' => 'create_exit_expr',
    'postprocessList' => 'postprocess_list',
    'createEmptyElemAttributes' => 'create_empty_elem_attributes',
    'parenthesizedArrowFunctions' => 'parenthesized_arrow_functions',
    'createdArrays' => 'created_arrays',
    'errorState' => 'error_state',
    'tokenPos' => 'token_pos',
];

foreach ($methodMap as $from => $to) {
    $content = str_replace('$this->' . $from, '$this->' . $to, $content);
}

$content = str_replace('$this->phpVersion', '$this->php_version', $content);
$content = str_replace('->setAttribute(', '->set_attribute(', $content);
$content = str_replace('->allowsAssignNewByReference()', '->allows_assign_new_by_reference()', $content);
$content = str_replace('->allowsInvalidOctals()', '->allows_invalid_octals()', $content);
$content = str_replace('->supportsUnicodeEscapes()', '->supports_unicode_escapes()', $content);
$content = str_replace('String_::fromString', 'String_::from_string', $content);
$content = str_replace('$this->check_classConst_', '$this->check_class_const', $content);
$content = str_replace('$this->check_classConst(', '$this->check_class_const(', $content);
$content = str_replace('$this->check_classMethod(', '$this->check_class_method(', $content);
$content = str_replace('$this->check_classModifier(', '$this->check_class_modifier(', $content);
$content = str_replace('$this->checkProperty_Hook(', '$this->check_property_hook(', $content);
$content = str_replace('$this->checkPropertyHookModifiers(', '$this->check_property_hook_modifiers(', $content);
$content = str_replace('Scalar\\MagicConst', 'Scalar\\Magic_Const', $content);
$content = str_replace('Scalar\\LNumber', 'Scalar\\Int_', $content);
$content = str_replace('Expr\\ClosureUse', 'Expr\\Closure_Use', $content);
$content = str_replace('Stmt\\Class_Const_', 'Stmt\\Class_Const', $content);
$content = str_replace('Stmt\\ElseIf_', 'Stmt\\Else_If_', $content);

file_put_contents($grammar, $content);
fwrite(STDOUT, 'fix-grammar-node-names: ' . count($map) . " node rules applied.\n");
