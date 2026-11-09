<?php

declare(strict_types=1);

$root = dirname(__DIR__);

/** @var list<string> */
$scanPhpPaths = [
    $root . '/lib',
    $root . '/test',
    $root . '/bin',
    $root . '/grammar',
];

/** @var list<string> */
$scanDocPaths = [
    $root . '/composer.json',
];

$failures = [];

/**
 * @return iterable<string>
 */
function php_files_to_scan(array $scanPaths): iterable
{
    foreach ($scanPaths as $path) {
        if (is_file($path) && substr($path, -4) === '.php') {
            yield $path;
            continue;
        }
        if (!is_dir($path)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && substr($file->getPathname(), -4) === '.php') {
                yield $file->getPathname();
            }
        }
    }
}

/**
 * @param list<string> $failures
 */
function scan_php_for_old_names(string $relative, string $contents, array &$failures): void
{
    $lines = explode("\n", $contents);
    $filtered = [];
    foreach ($lines as $line) {
        if (preg_match('/^\s*(\*\/|\*|\/\/|\/\*)/', $line)) {
            continue;
        }
        if (stripos($line, '@deprecated') !== false || stripos($line, '@param') !== false || stripos($line, '@return') !== false) {
            continue;
        }
        $filtered[] = $line;
    }
    $contents = implode("\n", $filtered);

    $patterns = [
        '/^\s*namespace\s+PhpParser\b/m',
        '/^\s*use\s+PhpParser\\\\/m',
        '/(?<!Php_)PhpParser\\\\[A-Za-z_]/',
        '/\bclass\s+BuilderFactory\b/',
        '/\bclass\s+ParserFactory\b/',
        '/\bclass\s+NodeAbstract\b/',
        '/\bclass\s+NodeVisitor\b/',
        '/\bclass\s+PrettyPrinter\b/',
        '/\bclass\s+ErrorHandler\b/',
        '/\bfunction\s+createForNewestSupportedVersion\b/',
        '/\bfunction\s+createForVersion\b/',
        '/\bphpyLang\.php\b/',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $contents)) {
            $failures[] = "{$relative}: matched forbidden pattern {$pattern}";
        }
    }
}

foreach (php_files_to_scan($scanPhpPaths) as $file) {
    $contents = file_get_contents($file);
    if ($contents === false) {
        $failures[] = "Could not read {$file}";
        continue;
    }
    $relative = strpos($file, $root) === 0 ? substr($file, strlen($root) + 1) : $file;
    scan_php_for_old_names($relative, $contents, $failures);
}

foreach ($scanDocPaths as $docPath) {
    if (!is_readable($docPath)) {
        continue;
    }
    $contents = file_get_contents($docPath);
    if ($contents === false) {
        $failures[] = "Could not read {$docPath}";
        continue;
    }
    $relative = substr($docPath, strlen($root) + 1);
    if (strpos($contents, '"PhpParser\\\\"') !== false) {
        $failures[] = "{$relative}: forbidden PSR-4 namespace PhpParser";
    }
}

$legacy_files = [
    $root . '/grammar/rebuildParsers.php',
    $root . '/grammar/phpyLang.php',
];
foreach ($legacy_files as $legacy) {
    if (is_file($legacy)) {
        $failures[] = 'grammar: legacy filename ' . basename($legacy);
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "check-rename-complete: {$failure}\n");
    }
    exit(1);
}

$demoFailures = [];
scan_php_for_old_names('demo-injection', "namespace PhpParser;\nclass BuilderFactory {}\n", $demoFailures);
if ($demoFailures === []) {
    fwrite(STDERR, "check-rename-complete: demo injection of old name did not fail as expected.\n");
    exit(1);
}

fwrite(STDOUT, "check-rename-complete: no pre-rename repo-owned names remain.\n");
exit(0);
