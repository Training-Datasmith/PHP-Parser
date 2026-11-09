<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$libRoot = $root . '/lib/PhpParser';

$gitOut = shell_exec('git diff --name-status 50f0d9c9 -- lib 2>/dev/null') ?? '';
$replacements = [];

foreach (explode("\n", trim($gitOut)) as $line) {
    if (!preg_match('/^R\d+\s+lib\/PhpParser\/(.+)\s+lib\/PhpParser\/(.+)$/', $line, $m)) {
        continue;
    }
    $oldBase = basename($m[1], '.php');
    $newBase = basename($m[2], '.php');
    if ($oldBase !== $newBase) {
        $replacements[$oldBase] = $newBase;
    }
}

$manual = [
    'DiffElem' => 'Diff_Elem',
    'ArrayItem' => 'Array_Item',
    'UseItem' => 'Use_Item',
    'MatchArm' => 'Match_Arm',
    'VariadicPlaceholder' => 'Variadic_Placeholder',
    'NullableType' => 'Nullable_Type',
    'VarLikeIdentifier' => 'Var_Like_Identifier',
    'FullyQualified' => 'Fully_Qualified',
    'InterpolatedString' => 'Interpolated_String',
    'InlineHTML' => 'Inline_Html',
    'ClassMethod' => 'Class_Method',
    'TraitUseAdaptation' => 'Trait_Use_Adaptation',
    'UnaryMinus' => 'Unary_Minus',
    'ClosureUse' => 'Closure_Use',
    'InterpolatedStringPart' => 'Interpolated_StringPart',
    'MagicConst\\' => 'Magic_Const\\',
    'FindingVisitor' => 'Finding_Visitor',
    'FirstFindingVisitor' => 'First_Finding_Visitor',
    'NodeConnectingVisitor' => 'Node_Connecting_Visitor',
    'ParentConnectingVisitor' => 'Parent_Connecting_Visitor',
];

$replacements = array_merge($manual, $replacements);
uksort($replacements, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

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
    $original = $content;

    if (!preg_match('/function\s+get_type\s*\(\)/', $content)) {
        continue;
    }

    $content = preg_replace_callback(
        "/function\s+get_type\s*\(\)\s*:\s*string\s*\{\s*return\s+'([^']+)';\s*\}/",
        static function (array $m) use ($replacements): string {
            $type = $m[1];
            foreach ($replacements as $from => $to) {
                $type = str_replace($from, $to, $type);
            }
            return "function get_type(): string {\n        return '{$type}';\n    }";
        },
        $content
    );

    if ($content !== $original) {
        file_put_contents($path, $content);
        ++$changed;
    }
}

fwrite(STDOUT, "fix-node-get-types: {$changed} files updated, " . count($replacements) . " rules.\n");
