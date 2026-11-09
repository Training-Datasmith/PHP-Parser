<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$libRoot = $root . '/lib/PhpParser';

/** @var array<string, class-string<Php_Parser\Node>> */
$map = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($libRoot, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $fileInfo) {
    if (substr($fileInfo->getFilename(), -4) !== '.php') {
        continue;
    }
    $rel = substr($fileInfo->getPathname(), strlen($libRoot) + 1);
    if (strpos($rel, 'Node/') !== 0 && $rel !== 'Node.php') {
        continue;
    }
    $content = file_get_contents($fileInfo->getPathname());
    if (!preg_match('/function\s+get_type\s*\(\)\s*:\s*string\s*\{[^}]*return\s*\'([^\']+)\'/s', $content, $m)) {
        continue;
    }
    $relClass = substr($rel, 0, -4);
    $class = 'Php_Parser\\' . str_replace('/', '\\', $relClass === 'Node.php' ? 'Node' : 'Node\\' . substr($relClass, 5));
    $map[$m[1]] = $class;
}

ksort($map);
$export = var_export($map, true);
$out = "<?php\n\ndeclare(strict_types=1);\n\nreturn {$export};\n";
file_put_contents($libRoot . '/node_type_map.php', $out);
fwrite(STDOUT, 'build-node-type-map: ' . count($map) . " entries.\n");
