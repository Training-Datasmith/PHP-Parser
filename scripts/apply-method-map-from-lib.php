<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$libRoot = $root . '/lib/PhpParser';
$testRoot = $root . '/test';

$methodMap = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($libRoot, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $fileInfo) {
    if (substr($fileInfo->getFilename(), -4) !== '.php') {
        continue;
    }
    $content = file_get_contents($fileInfo->getPathname());
    if (preg_match_all('/\bfunction\s+([a-z][a-z0-9_]*)\s*\(/', $content, $matches)) {
        foreach ($matches[1] as $snake) {
            if (strpos($snake, '_') === false) {
                continue;
            }
            $parts = explode('_', $snake);
            $camel = array_shift($parts);
            foreach ($parts as $part) {
                $camel .= ucfirst($part);
            }
            if ($camel !== $snake) {
                $methodMap[$camel] = $snake;
            }
        }
    }
}

uksort($methodMap, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

$testIterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($testRoot, FilesystemIterator::SKIP_DOTS)
);

foreach ($testIterator as $fileInfo) {
    if (substr($fileInfo->getFilename(), -4) !== '.php') {
        continue;
    }
    $path = $fileInfo->getPathname();
    $content = file_get_contents($path);
    $original = $content;

    foreach ($methodMap as $from => $to) {
        $content = preg_replace('/->' . preg_quote($from, '/') . '\s*\(/', '->' . $to . '(', $content);
        $content = preg_replace('/::' . preg_quote($from, '/') . '\s*\(/', '::' . $to . '(', $content);
    }

    // Callable string methods used in data providers: $this->name or 'name'
    foreach ($methodMap as $from => $to) {
        $content = preg_replace("/'{$from}'/", "'{$to}'", $content);
        $content = preg_replace('/"' . preg_quote($from, '/') . '"/', '"' . $to . '"', $content);
    }

    if ($content !== $original) {
        file_put_contents($path, $content);
    }
}

fwrite(STDOUT, 'apply-method-map-from-lib: ' . count($methodMap) . " methods.\n");
