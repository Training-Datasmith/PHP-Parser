<?php

declare(strict_types=1);

$root = dirname(__DIR__) . '/test';
$gitOut = shell_exec('git diff --name-status 50f0d9c9 -- lib 2>/dev/null') ?? '';
$replacements = [];

foreach (explode("\n", trim($gitOut)) as $line) {
    if (!preg_match('/^R\d+\s+lib\/PhpParser\/(.+)\s+lib\/PhpParser\/(.+)$/', $line, $m)) {
        continue;
    }
    $oldPath = $m[1];
    $newPath = $m[2];
    $oldClass = str_replace('/', '\\', substr($oldPath, 0, -4));
    $newClass = str_replace('/', '\\', substr($newPath, 0, -4));
    if ($oldClass !== $newClass) {
        $replacements[$oldClass] = $newClass;
    }
    $oldBase = basename($oldPath, '.php');
    $newBase = basename($newPath, '.php');
    if ($oldBase !== $newBase) {
        $replacements[$oldBase] = $newBase;
    }
}

$replacements['DiffElem'] = 'Diff_Elem';
$replacements['ArrayItem'] = 'Array_Item';
$replacements['UseItem'] = 'Use_Item';
$replacements['MatchArm'] = 'Match_Arm';
$replacements['VariadicPlaceholder'] = 'Variadic_Placeholder';
$replacements['NullableType'] = 'Nullable_Type';
$replacements['VarLikeIdentifier'] = 'Var_Like_Identifier';
$replacements['FullyQualified'] = 'Fully_Qualified';
$replacements['InterpolatedString'] = 'Interpolated_String';
$replacements['InlineHTML'] = 'Inline_Html';
$replacements['ClassMethod'] = 'Class_Method';
$replacements['TraitUseAdaptation'] = 'Trait_Use_Adaptation';
$replacements['UnaryMinus'] = 'Unary_Minus';
$replacements['ClosureUse'] = 'Closure_Use';
// Class references in formatPreservation eval snippets only (not parser dump type strings):
$formatPreservationOnly = [
    'BinaryOp\\' => 'Binary_Op\\',
    'MethodCall' => 'Method_Call',
    'StaticCall' => 'Static_Call',
    'ConstFetch' => 'Const_Fetch',
    'AttributeGroup' => 'Attribute_Group',
    'EnumCase' => 'Enum_Case',
    'PropertyHook' => 'Property_Hook',
    'ElseIf' => 'Else_If',
];
$replacements['MagicConst\\'] = 'Magic_Const\\';
$replacements['Node_Visitor\\Php_Parser\\'] = 'Php_Parser\\';
$replacements['FindingVisitor'] = 'Finding_Visitor';
$replacements['FirstFindingVisitor'] = 'First_Finding_Visitor';
$replacements['NodeConnectingVisitor'] = 'Node_Connecting_Visitor';
$replacements['ParentConnectingVisitor'] = 'Parent_Connecting_Visitor';
$replacements['->getComments('] = '->get_comments(';
$replacements['->setDocComment('] = '->set_doc_comment(';

uksort($replacements, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $fileInfo) {
    if (!$fileInfo->isFile()) {
        continue;
    }
    $path = $fileInfo->getPathname();
    $ext = substr($path, -5);
    if ($ext !== '.test' && substr($path, -4) !== '.php') {
        continue;
    }
    $content = file_get_contents($path);
    $original = $content;
    foreach ($replacements as $from => $to) {
        $content = str_replace($from, $to, $content);
    }
    if (str_contains($path, '/formatPreservation/')) {
        foreach ($formatPreservationOnly as $from => $to) {
            $content = str_replace($from, $to, $content);
        }
    }
    if ($content !== $original) {
        file_put_contents($path, $content);
    }
}

fwrite(STDOUT, 'fix-test-code-references: ' . count($replacements) . " rules.\n");
