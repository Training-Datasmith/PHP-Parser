<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$libRoot = $root . '/lib/PhpParser';

function fqcn_from_file(string $path, string $libRoot): string
{
    $rel = substr($path, strlen($libRoot) + 1);
    $rel = substr($rel, 0, -4);
    $parts = explode('/', $rel);
    $class = array_pop($parts);
    $ns = 'Php_Parser';
    if ($parts !== []) {
        $ns .= '\\' . implode('\\', $parts);
    }

    return $ns . '\\' . $class;
}

function type_from_class(string $className): string
{
    $prefix = 'Php_Parser\\Node\\';
    if (strncmp($className, $prefix, strlen($prefix)) === 0) {
        return str_replace('\\', '_', substr($className, strlen($prefix)));
    }
    $prefix = 'Php_Parser\\Internal\\';
    if (strncmp($className, $prefix, strlen($prefix)) === 0) {
        return str_replace('\\', '_', substr($className, strlen($prefix)));
    }

    throw new RuntimeException($className);
}

/** @var array<string, string> */
$map = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($libRoot, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $fileInfo) {
    if (substr($fileInfo->getFilename(), -4) !== '.php') {
        continue;
    }
    $path = $fileInfo->getPathname();
    $content = file_get_contents($path);
    if (!preg_match("/function get_type\(\)[^{]+return '([^']+)'/s", $content, $m)) {
        continue;
    }
    $old = $m[1];
    try {
        $fqcn = fqcn_from_file($path, $libRoot);
        $new = type_from_class($fqcn);
    } catch (Throwable $e) {
        continue;
    }
    if ($old !== $new) {
        $map[$old] = $new;
    }
}

uksort($map, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

$testRoots = [$root . '/test/code', $root . '/test/PhpParser'];
$changed = 0;
foreach ($testRoots as $testRoot) {
    if (!is_dir($testRoot)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($testRoot, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $fileInfo) {
        $name = $fileInfo->getFilename();
        if (!preg_match('/\.(test|php)$/', $name)) {
            continue;
        }
        $path = $fileInfo->getPathname();
        $content = file_get_contents($path);
        $original = $content;
        foreach ($map as $from => $to) {
            $content = str_replace($from, $to, $content);
        }
        if ($content !== $original) {
            file_put_contents($path, $content);
            ++$changed;
        }
    }
}

fwrite(STDOUT, 'sync-test-dump-types: ' . count($map) . " type rules, {$changed} files.\n");
