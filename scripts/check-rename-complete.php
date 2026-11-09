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
$scanTextPaths = [
    $root . '/README.md',
    $root . '/CHANGELOG.md',
    $root . '/doc',
    $root . '/grammar/php.y',
    $root . '/grammar/parser.template',
];

/** @var list<string> */
$scanTestDumpPaths = [
    $root . '/test/code',
];

$failures = [];

/**
 * @return iterable<string>
 */
function files_to_scan(array $scanPaths, array $extensions): iterable
{
    foreach ($scanPaths as $path) {
        if (is_file($path)) {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if (in_array($ext, $extensions, true) || in_array(basename($path), $extensions, true)) {
                yield $path;
            }
            continue;
        }
        if (!is_dir($path)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $name = $file->getFilename();
            foreach ($extensions as $ext) {
                $suffix = '.' . $ext;
                if ($ext === $name
                    || (strlen($name) >= strlen($suffix) && substr($name, -strlen($suffix)) === $suffix)
                    || ($ext === 'test' && strlen($name) > 5 && substr($name, -5) === '.test')) {
                    yield $file->getPathname();
                    break;
                }
            }
        }
    }
}

/**
 * Upstream names that must remain in docs or code references.
 *
 * @return bool
 */
function is_allowlisted_line(string $line): bool
{
    $allowPatterns = [
        '/\bPHPUnit\\\\/',
        '/\bT_[A-Z0-9_]+\b/',
        '/\b(startLine|endLine|startTokenPos|endTokenPos|startFilePos|endFilePos)\b/',
        '/\bdumpComments\b/',
        '/\bdumpPositions\b/',
        '/\bdumpOtherAttributes\b/',
        '/\bphpVersion\b/',
        '/\bshortArraySyntax\b/',
        '/\bnodeType\b/',
        '/\bPHP_VERSION\b/',
        '/\bJSON_/',
        '/\b__LINE__\b/',
        '/\b__FILE__\b/',
        '/\b__DIR__\b/',
        '/\b__CLASS__\b/',
        '/\b__METHOD__\b/',
        '/\b__FUNCTION__\b/',
        '/\bnikic\/php-parser\b/',
        '/\bPackagist\b/',
        '/\bcomposer\.json\b.*PhpParser\\\\"Psr/', // psr in composer examples elsewhere
    ];
    foreach ($allowPatterns as $pattern) {
        if (preg_match($pattern, $line)) {
            return true;
        }
    }

    return false;
}

/**
 * @param list<string> $failures
 */
function is_historical_doc(string $relative): bool
{
    return (bool) preg_match('#^(?:.*/)?(CHANGELOG\.md|CONTRIBUTING\.md|UPGRADE-.*\.md)$#', $relative);
}

/**
 * @param list<string> $failures
 */
function scan_for_old_names(string $relative, string $contents, array &$failures): void
{
    $lines = explode("\n", $contents);
    $historical = is_historical_doc($relative);
    foreach ($lines as $i => $line) {
        if (is_allowlisted_line($line)) {
            continue;
        }

        $patterns = [
            '/^\s*namespace\s+PhpParser\b/',
            '/^\s*use\s+PhpParser\\\\/',
            '/(?<!Php_)PhpParser\\\\[A-Za-z_]/',
            '/\bclass\s+BuilderFactory\b/',
            '/\bclass\s+ParserFactory\b/',
            '/\bclass\s+NodeAbstract\b/',
            '/\bclass\s+NodeVisitor\b/',
            '/\bclass\s+PrettyPrinter\b/',
            '/\bclass\s+ErrorHandler\b/',
            '/\bfunction\s+createForNewestSupportedVersion\b/',
            '/\bfunction\s+createForVersion\b/',
            '/\bfunction\s+getSubNodeNames\b/',
            '/\bfunction\s+getType\b/',
            '/\b->getSubNodeNames\s*\(/',
            '/\b->getType\s*\(/',
            '/\b->createForNewestSupportedVersion\s*\(/',
            '/\b->createForVersion\s*\(/',
            '/\bphpyLang\.php\b/',
            '/\brebuildParsers\.php\b/',
            '/\bStmt\\\\ClassMethod\b/',
            '/\bNode\\\\UseItem\b/',
            '/\bbyRef\s*:/',
            '/\battrGroups\s*:/',
            '/\breturnType\s*:/',
            '/\bkeyVar\s*:/',
            '/\bvalueVar\s*:/',
            '/\bscalarType\s*:/',
            '/\bnewModifier\s*:/',
            '/\bnewName\s*:/',
            '/["\']byRef["\']/',
            '/["\']attrGroups["\']/',
            '/["\']returnType["\']/',
            '/["\']namespacedName["\']/',
            '/["\']keyVar["\']/',
            '/["\']scalarType["\']/',
            '/["\']newModifier["\']/',
            '/["\']newName["\']/',
            '/->returnType\b/',
            '/->attrGroups\b/',
            '/->keyVar\b/',
            '/->byRef\b/',
            '/->scalarType\b/',
            '/->newName\b/',
            '/->newModifier\b/',
            '/@param\s+[^\s$]+\s+\$newModifier\b/',
            '/@param\s+[^\s$]+\s+\$newName\b/',
        ];

        if (!$historical) {
            $patterns = array_merge($patterns, [
                '/(?<!_)hasAttribute\s*\(/',
                '/\bgetAttributes\s*\(/',
                '/\bgetAttribute\s*\(/',
                '/\bsetAttribute\s*\(/',
                '/\bgetStartLine\s*\(/',
                '/\bgetDocComment\s*\(/',
                '/\bcreateForHostVersion\b/',
                '/\bcreateForVersion\b/',
                '/\bcreateForNewestSupportedVersion\b/',
                '/\bprettyPrintFile\s*\(/',
                '/\bprettyPrintExpr\s*\(/',
                '/\bprettyPrint\s*\(/',
            ]);
        }

        $patterns = array_merge($patterns, [
            '/\b->hasAttribute\s*\(/',
            '/\b->getAttributes\s*\(/',
            '/\b->getAttribute\s*\(/',
            '/\b->setAttribute\s*\(/',
            '/\b->getStartLine\s*\(/',
            '/\b->getDocComment\s*\(/',
        ]);

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line)) {
                $failures[] = "{$relative}:" . ($i + 1) . ": matched {$pattern}";
            }
        }
    }
}

foreach (files_to_scan($scanPhpPaths, ['php']) as $file) {
    $contents = file_get_contents($file);
    if ($contents === false) {
        $failures[] = "Could not read {$file}";
        continue;
    }
    $relative = strpos($file, $root) === 0 ? substr($file, strlen($root) + 1) : $file;
    scan_for_old_names($relative, $contents, $failures);
}

foreach (files_to_scan($scanTextPaths, ['md', 'markdown', 'y', 'template']) as $file) {
    $contents = file_get_contents($file);
    if ($contents === false) {
        $failures[] = "Could not read {$file}";
        continue;
    }
    $relative = strpos($file, $root) === 0 ? substr($file, strlen($root) + 1) : $file;
    scan_for_old_names($relative, $contents, $failures);
}

foreach (files_to_scan($scanTestDumpPaths, ['test']) as $file) {
    $contents = file_get_contents($file);
    if ($contents === false) {
        $failures[] = "Could not read {$file}";
        continue;
    }
    $relative = strpos($file, $root) === 0 ? substr($file, strlen($root) + 1) : $file;
    scan_for_old_names($relative, $contents, $failures);
}

if (is_readable($root . '/composer.json')) {
    $contents = file_get_contents($root . '/composer.json');
    if ($contents !== false && strpos($contents, '"PhpParser\\\\"') !== false) {
        $failures[] = 'composer.json: forbidden PSR-4 namespace PhpParser';
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

$patternDemos = [
    'demo-namespace' => "namespace PhpParser;\nclass BuilderFactory {}\n",
    'demo-quoted-byRef' => '{"byRef": false}',
    'demo-quoted-returnType' => '"returnType": null',
    'demo-property-access' => '$stmts[0]->returnType = null;',
    'demo-param-newModifier' => "/**\n * @param int \$newModifier foo\n */\n",
    'demo-method-hasAttribute' => 'Use hasAttribute() to read metadata.',
];

foreach ($patternDemos as $label => $sample) {
    $demoFailures = [];
    scan_for_old_names($label, $sample, $demoFailures);
    if ($demoFailures === []) {
        fwrite(STDERR, "check-rename-complete: pattern demo \"{$label}\" did not fail as expected.\n");
        exit(1);
    }
}

fwrite(STDOUT, "check-rename-complete: no pre-rename repo-owned names remain.\n");
exit(0);
