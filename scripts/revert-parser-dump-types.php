<?php

declare(strict_types=1);

$root = dirname(__DIR__) . '/test/code/parser';
$replacements = [
    'Expr_Method_Call' => 'Expr_MethodCall',
    'Expr_Static_Call' => 'Expr_StaticCall',
    'Expr_Func_Call' => 'Expr_FuncCall',
    'Expr_NullsafeMethod_Call' => 'Expr_NullsafeMethodCall',
    'Expr_Const_Fetch' => 'Expr_Const_Fetch',
    'Expr_Class_Const_Fetch' => 'Expr_ClassConst_Fetch',
    'Expr_ClassConst_Fetch' => 'Expr_ClassConst_Fetch',
    'Expr_Array_Dim_Fetch' => 'Expr_ArrayDimFetch',
    'Expr_Property_Fetch' => 'Expr_PropertyFetch',
    'Expr_Static_Property_Fetch' => 'Expr_StaticPropertyFetch',
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$changed = 0;
foreach ($iterator as $fileInfo) {
    if (!$fileInfo->isFile() || substr($fileInfo->getFilename(), -5) !== '.test') {
        continue;
    }
    $path = $fileInfo->getPathname();
    $content = file_get_contents($path);
    $original = $content;
    foreach ($replacements as $from => $to) {
        $content = str_replace($from, $to, $content);
    }
    if ($content !== $original) {
        file_put_contents($path, $content);
        ++$changed;
    }
}

fwrite(STDOUT, "revert-parser-dump-types: {$changed} files.\n");
