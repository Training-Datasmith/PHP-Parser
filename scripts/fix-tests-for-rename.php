<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$testRoot = $root . '/test/PhpParser';

$classMap = [
    'ParserFactory' => 'Parser_Factory',
    'ParserTestAbstract' => 'Parser_Test_Abstract',
    'CodeTestAbstract' => 'Code_Test_Abstract',
    'CodeTestParser' => 'Code_Test_Parser',
    'CodeParsingTest' => 'Code_Parsing_Test',
    'NodeDumper' => 'Node_Dumper',
    'NodeTraverser' => 'Node_Traverser',
    'NodeVisitorAbstract' => 'Node_Visitor_Abstract',
    'NodeVisitorForTesting' => 'Node_Visitor_For_Testing',
    'NodeFinder' => 'Node_Finder',
    'BuilderHelpers' => 'Builder_Helpers',
    'BuilderFactory' => 'Builder_Factory',
    'ConstExprEvaluator' => 'Const_Expr_Evaluator',
    'PhpVersion' => 'Php_Version',
    'NameContext' => 'Name_Context',
    'JsonDecoder' => 'Json_Decoder',
    'FullyQualified' => 'Fully_Qualified',
    'ErrorHandler' => 'Error_Handler',
    'PrettyPrinter' => 'Pretty_Printer',
    'PrintableNewAnonClassNode' => 'Printable_New_Anon_Class_Node',
    'InvalidTokenLexer' => 'Invalid_Token_Lexer',
];

$methodMap = [
    'createForNewestSupportedVersion' => 'create_for_newest_supported_version',
    'createForVersion' => 'create_for_version',
    'createForHostVersion' => 'create_for_host_version',
    'getNewestSupported' => 'get_newest_supported',
    'getHostVersion' => 'get_host_version',
    'fromString' => 'from_string',
    'getStartLine' => 'get_start_line',
    'getEndLine' => 'get_end_line',
    'getStartFilePos' => 'get_start_file_pos',
    'getEndFilePos' => 'get_end_file_pos',
    'getStartTokenPos' => 'get_start_token_pos',
    'getEndTokenPos' => 'get_end_token_pos',
    'getType' => 'get_type',
    'getSubNodeNames' => 'get_sub_node_names',
    'getErrors' => 'get_errors',
    'hasColumnInfo' => 'has_column_info',
    'getMessageWithColumnInfo' => 'get_message_with_column_info',
    'getRawMessage' => 'get_raw_message',
    'normalizeValue' => 'normalize_value',
    'normalizeDocComment' => 'normalize_doc_comment',
    'normalizeAttribute' => 'normalize_attribute',
    'normalizeStmt' => 'normalize_stmt',
    'normalizeName' => 'normalize_name',
    'normalizeIdentifier' => 'normalize_identifier',
    'addVisitor' => 'add_visitor',
    'enterNode' => 'enter_node',
    'leaveNode' => 'leave_node',
    'prettyPrint' => 'pretty_print',
    'prettyPrintFile' => 'pretty_print_file',
    'formatErrorMessage' => 'format_error_message',
    'parseModeLine' => 'parse_mode_line',
    'getParseOutput' => 'get_parse_output',
    'getTests' => 'get_tests',
    'parseTest' => 'parse_test',
    'reconstructTest' => 'reconstruct_test',
    'checkAttributes' => 'check_attributes',
    'isHostVersion' => 'is_host_version',
    'isSpecialClassName' => 'is_special_class_name',
    'isPromoted' => 'is_promoted',
    'isFinal' => 'is_final',
    'isPublic' => 'is_public',
    'isProtected' => 'is_protected',
    'isPrivate' => 'is_private',
    'isStatic' => 'is_static',
    'isAbstract' => 'is_abstract',
    'isReadonly' => 'is_readonly',
    'isVariadic' => 'is_variadic',
    'returnsByRef' => 'returns_by_ref',
    'getParams' => 'get_params',
    'getStmts' => 'get_stmts',
    'getReturnType' => 'get_return_type',
    'getAttrGroups' => 'get_attr_groups',
    'getIterator' => 'get_iterator',
    'getTokens' => 'get_tokens',
    'getErrors' => 'get_errors',
    'handleError' => 'handle_error',
    'getDocComment' => 'get_doc_comment',
    'setDocComment' => 'set_doc_comment',
    'setAttribute' => 'set_attribute',
    'hasAttribute' => 'has_attribute',
    'getAttribute' => 'get_attribute',
    'getAttributes' => 'get_attributes',
    'getComments' => 'get_comments',
    'getLine' => 'get_line',
    'isIgnorable' => 'is_ignorable',
    'decode' => 'decode',
    'dump' => 'dump',
    'find' => 'find',
    'traverse' => 'traverse',
    'beforeTraverse' => 'before_traverse',
    'enterNode' => 'enter_node',
    'leaveNode' => 'leave_node',
    'afterTraverse' => 'after_traverse',
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($testRoot, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $fileInfo) {
    if (!$fileInfo->isFile() || substr($fileInfo->getFilename(), -4) !== '.php') {
        continue;
    }

    $path = $fileInfo->getPathname();
    $expectedClass = basename($path, '.php');
    $relDir = trim(str_replace($testRoot, '', dirname($path)), '/');
    $ns = 'Php_Parser' . ($relDir !== '' ? '\\' . str_replace('/', '\\', $relDir) : '');

    $content = file_get_contents($path);
    $content = preg_replace('/namespace\s+[^;]+;/', 'namespace ' . $ns . ';', $content, 1);
    $content = preg_replace(
        '/^(\s*)class\s+\w+\s+extends\s+\\\\?PHPUnit\\\\Framework\\\\TestCase/m',
        '$1class ' . $expectedClass . ' extends \\PHPUnit\\Framework\\TestCase',
        $content,
        1
    );

    $content = str_replace('PhpParser\\', 'Php_Parser\\', $content);
    $content = str_replace('namespace Php_Parser;', 'namespace Php_Parser;', $content);

    foreach ($classMap as $from => $to) {
        $content = preg_replace('/\b' . preg_quote($from, '/') . '\b/', $to, $content);
    }

    foreach ($methodMap as $from => $to) {
        $content = preg_replace('/->' . preg_quote($from, '/') . '\s*\(/', '->' . $to . '(', $content);
        $content = preg_replace('/::' . preg_quote($from, '/') . '\s*\(/', '::' . $to . '(', $content);
    }

    // PHPUnit / PHP native names restored if accidentally changed
    $content = str_replace('Php_Parser\\Framework\\TestCase', 'PHPUnit\\Framework\\TestCase', $content);
    $content = str_replace('$this->assert_equals', '$this->assertEquals', $content);
    $content = str_replace('$this->assert_same', '$this->assertSame', $content);
    $content = str_replace('$this->assert_true', '$this->assertTrue', $content);
    $content = str_replace('$this->assert_false', '$this->assertFalse', $content);
    $content = str_replace('$this->assert_null', '$this->assertNull', $content);
    $content = str_replace('$this->assert_instance_of', '$this->assertInstanceOf', $content);
    $content = str_replace('$this->assert_empty', '$this->assertEmpty', $content);
    $content = str_replace('$this->assert_count', '$this->assertCount', $content);
    $content = str_replace('$this->expect_exception', '$this->expectException', $content);
    $content = str_replace('$this->expect_exception_message', '$this->expectExceptionMessage', $content);
    $content = str_replace('$this->mark_test_skipped', '$this->markTestSkipped', $content);

    file_put_contents($path, $content);
}

$bootstrap = $root . '/test/bootstrap.php';
$boot = file_get_contents($bootstrap);
$boot = preg_replace('/namespace\s+[^;]+;/', 'namespace Php_Parser;', $boot, 1);
$boot = str_replace('PhpParser\\', 'Php_Parser\\', $boot);
file_put_contents($bootstrap, $boot);

$updateTests = $root . '/test/update_Tests.php';
if (is_file($updateTests)) {
    $u = file_get_contents($updateTests);
    $u = str_replace('PhpParser\\', 'Php_Parser\\', $u);
    $u = preg_replace('/\bCodeParsingTest\b/', 'Code_Parsing_Test', $u);
    $u = preg_replace('/\bPrettyPrinterTest\b/', 'Pretty_Printer_Test', $u);
    $u = preg_replace('/\bCodeTestParser\b/', 'Code_Test_Parser', $u);
    file_put_contents($updateTests, $u);
}

fwrite(STDOUT, "fix-tests-for-rename: updated test tree.\n");
