<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$testRoot = $root . '/test';

$gitOut = shell_exec('git diff --name-status 50f0d9c9 -- lib 2>/dev/null') ?? '';
$classMap = [];

foreach (explode("\n", trim($gitOut)) as $line) {
    if (!preg_match('/^R\d+\s+(\S+)\s+(\S+)$/', $line, $m)) {
        continue;
    }
    [, $oldPath, $newPath] = $m;
    $oldBase = basename($oldPath, '.php');
    $newBase = basename($newPath, '.php');
    if ($oldBase !== $newBase) {
        $classMap[$oldBase] = $newBase;
    }

    $oldRel = preg_replace('#^lib/PhpParser/#', '', $oldPath);
    $newRel = preg_replace('#^lib/PhpParser/#', '', $newPath);
    $oldNs = str_replace('/', '\\', dirname($oldRel));
    $newNs = str_replace('/', '\\', dirname($newRel));
    if ($oldNs !== '.' && $newNs !== '.' && $oldNs !== $newNs) {
        $classMap[str_replace('\\', '\\\\', $oldNs)] = str_replace('\\', '\\\\', $newNs);
    }
}

$topLevel = [
    'ParserFactory' => 'Parser_Factory',
    'ParserAbstract' => 'Parser_Abstract',
    'NodeAbstract' => 'Node_Abstract',
    'NodeDumper' => 'Node_Dumper',
    'NodeFinder' => 'Node_Finder',
    'NodeTraverser' => 'Node_Traverser',
    'NodeVisitor' => 'Node_Visitor',
    'NodeVisitorAbstract' => 'Node_Visitor_Abstract',
    'BuilderFactory' => 'Builder_Factory',
    'BuilderHelpers' => 'Builder_Helpers',
    'ConstExprEvaluator' => 'Const_Expr_Evaluator',
    'PhpVersion' => 'Php_Version',
    'NameContext' => 'Name_Context',
    'JsonDecoder' => 'Json_Decoder',
    'PrettyPrinter' => 'Pretty_Printer',
    'PrettyPrinterAbstract' => 'Pretty_Printer_Abstract',
    'ErrorHandler' => 'Error_Handler',
    'ClassConst_Fetch' => 'Class_Const_Fetch',
    'ClassConst' => 'Class_Const',
    'ClassMethod' => 'Class_Method',
    'PropertyFetch' => 'Property_Fetch',
    'AttributeGroup' => 'Attribute_Group',
    'MagicConst' => 'Magic_Const',
];
$classMap = array_merge($topLevel, $classMap);

uksort($classMap, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($testRoot, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $fileInfo) {
    if (substr($fileInfo->getFilename(), -4) !== '.php') {
        continue;
    }
    $path = $fileInfo->getPathname();
    $content = file_get_contents($path);
    $original = $content;
    foreach ($classMap as $from => $to) {
        if (strpos($from, '\\') !== false) {
            $content = str_replace($from, $to, $content);
            continue;
        }
        $content = preg_replace('/(?<![A-Za-z0-9_\\\\])' . preg_quote($from, '/') . '(?![A-Za-z0-9_])/', $to, $content);
    }
    if ($content !== $original) {
        file_put_contents($path, $content);
    }
}

fwrite(STDOUT, 'apply-lib-renames-to-tests: ' . count($classMap) . " replacements.\n");
