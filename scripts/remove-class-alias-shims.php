<?php

declare(strict_types=1);

$root = dirname(__DIR__) . '/lib';

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$changed = 0;
foreach ($iterator as $fileInfo) {
    if (substr($fileInfo->getFilename(), -4) !== '.php') {
        continue;
    }
    $path = $fileInfo->getPathname();
    $content = file_get_contents($path);
    $original = $content;
    $content = preg_replace('/\n\/\/ @deprecated compatibility alias\nclass_alias\([^\)]+\);\s*/', "\n", $content);
    $content = preg_replace('/\nclass_alias\([^\)]+\);\s*/', "\n", $content);
    if ($content !== $original) {
        file_put_contents($path, $content);
        ++$changed;
    }
}

$stubPaths = [
    dirname(__DIR__) . '/lib/PhpParser/Node/Expr/Array_Item.php',
    dirname(__DIR__) . '/lib/PhpParser/Node/Expr/Closure_Use.php',
    dirname(__DIR__) . '/lib/PhpParser/Node/Stmt/PropertyProperty.php',
    dirname(__DIR__) . '/lib/PhpParser/Node/Stmt/StaticVar.php',
    dirname(__DIR__) . '/lib/PhpParser/Node/Stmt/UseUse.php',
    dirname(__DIR__) . '/lib/PhpParser/Node/Stmt/DeclareDeclare.php',
];
foreach ($stubPaths as $stub) {
    if (is_file($stub)) {
        unlink($stub);
        ++$changed;
    }
}

fwrite(STDOUT, "remove-class-alias-shims: {$changed} updates.\n");
