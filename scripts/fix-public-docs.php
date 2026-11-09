<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$classMap = [
    'ParserFactory' => 'Parser_Factory',
    'NodeDumper' => 'Node_Dumper',
    'NodeTraverser' => 'Node_Traverser',
    'NodeVisitorAbstract' => 'Node_Visitor_Abstract',
    'PrettyPrinter' => 'Pretty_Printer',
    'BuilderFactory' => 'Builder_Factory',
    'ConstExprEvaluator' => 'Const_Expr_Evaluator',
    'ErrorHandler' => 'Error_Handler',
    'JsonDecoder' => 'Json_Decoder',
    'PhpVersion' => 'Php_Version',
    'NameContext' => 'Name_Context',
];

$methodMap = [
    'createForNewestSupportedVersion' => 'create_for_newest_supported_version',
    'createForVersion' => 'create_for_version',
    'getSubNodeNames' => 'get_sub_node_names',
    'getType' => 'get_type',
    'prettyPrint' => 'pretty_print',
    'prettyPrintFile' => 'pretty_print_file',
    'addVisitor' => 'add_visitor',
    'enterNode' => 'enter_node',
    'leaveNode' => 'leave_node',
    'beforeTraverse' => 'before_traverse',
    'afterTraverse' => 'after_traverse',
    'getStartLine' => 'get_start_line',
    'setAttribute' => 'set_attribute',
    'getAttribute' => 'get_attribute',
];

$paths = [
    $root . '/README.md',
    $root . '/CHANGELOG.md',
    $root . '/doc',
];

$changed = 0;
foreach ($paths as $scan) {
    if (is_file($scan)) {
        $files = [$scan];
    } else {
        $files = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($scan, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile()) {
                $files[] = $f->getPathname();
            }
        }
    }

    foreach ($files as $path) {
        $content = file_get_contents($path);
        $original = $content;
        $content = str_replace('PhpParser\\', 'Php_Parser\\', $content);
        foreach ($classMap as $from => $to) {
            $content = preg_replace('/\b' . preg_quote($from, '/') . '\b/', $to, $content);
        }
        foreach ($methodMap as $from => $to) {
            $content = preg_replace('/->' . preg_quote($from, '/') . '\b/', '->' . $to, $content);
        }
        if ($content !== $original) {
            file_put_contents($path, $content);
            ++$changed;
        }
    }
}

fwrite(STDOUT, "fix-public-docs: {$changed} files.\n");
