<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$libRoot = $root . '/lib/PhpParser';

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
    $rel = 'lib/PhpParser/' . substr($path, strlen($libRoot) + 1);
    $content = file_get_contents($path);
    if (!preg_match("/function get_type\(\)[^{]+return '([^']+)'/s", $content, $newMatch)) {
        continue;
    }
    $new = $newMatch[1];
    $gitPath = escapeshellarg($rel);
    $oldSrc = shell_exec("git show 50f0d9c9:{$gitPath} 2>/dev/null") ?? '';
    if ($oldSrc === '') {
        continue;
    }
    if (!preg_match('/function (get_type|getType)\(\)[^{]+return \'([^\']+)\'/s', $oldSrc, $oldMatch)) {
        continue;
    }
    $old = $oldMatch[2];
    if ($old !== $new) {
        $map[$old] = $new;
    }
}

uksort($map, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

$testRoots = [$root . '/test/code', $root . '/test/PhpParser', $root . '/doc'];
$changed = 0;
foreach ($testRoots as $testRoot) {
    if (!is_dir($testRoot)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($testRoot, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }
        $name = $fileInfo->getFilename();
        if (!preg_match('/\.(test|php|markdown)$/', $name)) {
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

fwrite(STDOUT, 'sync-test-dump-types-from-git: ' . count($map) . " rules, {$changed} files.\n");
foreach ($map as $from => $to) {
    fwrite(STDOUT, "  {$from} => {$to}\n");
}
