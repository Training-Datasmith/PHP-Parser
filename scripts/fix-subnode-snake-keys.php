<?php

declare(strict_types=1);

$root = dirname(__DIR__);

/** @var array<string, string> */
$subnodeKeys = [
    'attrGroups' => 'attr_groups',
    'byRef' => 'by_ref',
    'returnType' => 'return_type',
    'keyVar' => 'key_var',
    'valueVar' => 'value_var',
    'scalarType' => 'scalar_type',
    'namespacedName' => 'namespaced_name',
];

/** @var list<string> */
$paths = [
    $root . '/lib',
    $root . '/test',
    $root . '/grammar/php.y',
];

function replace_subnode_keys(string $content, array $subnodeKeys): string
{
    foreach ($subnodeKeys as $from => $to) {
        $content = str_replace("'{$from}'", "'{$to}'", $content);
        $content = str_replace("\"{$from}\"", "\"{$to}\"", $content);
        $content = preg_replace('/\b' . preg_quote($from, '/') . '\s*:/', $to . ':', $content);
        $content = preg_replace(
            "/(\\\$sub_nodes|\\\$subNodes)\\['" . preg_quote($from, '/') . "'\\]/",
            '$1[\'' . $to . '\']',
            $content
        );
        $content = preg_replace(
            '/(=>|\'|\")' . preg_quote($from, '/') . '(\'|\")/',
            '$1' . $to . '$2',
            $content
        );
    }

    return $content;
}

$changedFiles = 0;
foreach ($paths as $scan) {
    if (is_file($scan)) {
        $content = file_get_contents($scan);
        $next = replace_subnode_keys($content, $subnodeKeys);
        if ($next !== $content) {
            file_put_contents($scan, $next);
            ++$changedFiles;
        }
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($scan, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }
        $name = $fileInfo->getFilename();
        if (!preg_match('/\.(php|test|y)$/', $name)) {
            continue;
        }
        $path = $fileInfo->getPathname();
        $content = file_get_contents($path);
        $next = replace_subnode_keys($content, $subnodeKeys);
        if ($next !== $content) {
            file_put_contents($path, $next);
            ++$changedFiles;
        }
    }
}

fwrite(STDOUT, "fix-subnode-snake-keys: {$changedFiles} paths updated.\n");
