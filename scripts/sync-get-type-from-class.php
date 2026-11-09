<?php

declare(strict_types=1);

/**
 * Set each Node::get_type() return to the path under Php_Parser\Node\ with \ -> _.
 */

$root = dirname(__DIR__);
$libRoot = $root . '/lib/PhpParser';

function type_string_for_class(string $className): string
{
  $prefix = 'Php_Parser\\Node\\';
  if (strncmp($className, $prefix, strlen($prefix)) === 0) {
      return str_replace('\\', '_', substr($className, strlen($prefix)));
  }
  $prefix = 'Php_Parser\\Internal\\';
  if (strncmp($className, $prefix, strlen($prefix)) === 0) {
      return str_replace('\\', '_', substr($className, strlen($prefix)));
  }

  throw new RuntimeException("Unsupported class for get_type: {$className}");
}

function fqcn_from_file(string $path, string $libRoot): ?string
{
    $rel = substr($path, strlen($libRoot) + 1);
    if (substr($rel, -4) !== '.php') {
        return null;
    }
    $rel = substr($rel, 0, -4);
    $parts = explode('/', $rel);
    $class = array_pop($parts);
    $ns = 'Php_Parser';
    if ($parts !== []) {
        $ns .= '\\' . implode('\\', $parts);
    }

    return $ns . '\\' . $class;
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($libRoot, FilesystemIterator::SKIP_DOTS)
);

$changed = 0;
foreach ($iterator as $fileInfo) {
    if (substr($fileInfo->getFilename(), -4) !== '.php') {
        continue;
    }
    $path = $fileInfo->getPathname();
    $content = file_get_contents($path);
    if (!preg_match('/function\s+get_type\s*\(\)/', $content)) {
        continue;
    }

    $fqcn = fqcn_from_file($path, $libRoot);
    if ($fqcn === null) {
        continue;
    }

    try {
        $want = type_string_for_class($fqcn);
    } catch (RuntimeException $e) {
        continue;
    }

    $newContent = preg_replace(
        '/function\s+get_type\s*\(\)\s*:\s*string\s*\{[^\}]*return\s*\'[^\']*\'[^\}]*\}/s',
        "function get_type(): string {\n        return '{$want}';\n    }",
        $content,
        1,
        $count
    );

    if ($count && $newContent !== $content) {
        file_put_contents($path, $newContent);
        ++$changed;
    }
}

fwrite(STDOUT, "sync-get-type-from-class: {$changed} files updated.\n");
