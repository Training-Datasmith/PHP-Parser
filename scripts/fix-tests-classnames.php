<?php

declare(strict_types=1);

$root = dirname(__DIR__) . '/test';

$classReplacements = [
    'NodeAbstract' => 'Node_Abstract',
    'NodeConnectingVisitorTest' => 'Node_Connecting_Visitor_Test',
    'ParentConnectingVisitorTest' => 'Parent_Connecting_Visitor_Test',
    'Builder\\ClassConst' => 'Builder\\Class_Const',
    'Builder\\EnumCase' => 'Builder\\Enum_Case',
    'Builder\\TraitUse' => 'Builder\\Trait_Use',
    'Builder\\TraitUseAdaptation' => 'Builder\\Trait_Use_Adaptation',
    'Stmt\\ClassConst' => 'Stmt\\Class_Const',
    'Stmt\\ClassMethod' => 'Stmt\\Class_Method',
    'Stmt\\TraitUse' => 'Stmt\\Trait_Use',
    'Stmt\\TraitUseAdaptation' => 'Stmt\\Trait_Use_Adaptation',
    'Node\\AttributeGroup' => 'Node\\Attribute_Group',
    'Node\\PropertyHook' => 'Node\\Property_Hook',
    'Node\\ArrayItem' => 'Node\\Array_Item',
    'Node\\UseItem' => 'Node\\Use_Item',
    'Node\\MatchArm' => 'Node\\Match_Arm',
    'Expr\\ClassConstFetch' => 'Expr\\Class_Const_Fetch',
    'Expr\\ConstFetch' => 'Expr\\Const_Fetch',
    'Expr\\FuncCall' => 'Expr\\Func_Call',
    'Expr\\MethodCall' => 'Expr\\Method_Call',
    'Expr\\StaticCall' => 'Expr\\Static_Call',
    'Expr\\ArrayDimFetch' => 'Expr\\Array_Dim_Fetch',
    'Expr\\PropertyFetch' => 'Expr\\Property_Fetch',
    'Expr\\StaticPropertyFetch' => 'Expr\\Static_Property_Fetch',
    'Expr\\NullsafeMethodCall' => 'Expr\\Nullsafe_Method_Call',
    'Expr\\NullsafePropertyFetch' => 'Expr\\Nullsafe_Property_Fetch',
    'Expr\\ErrorSuppress' => 'Expr\\Error_Suppress',
    'Expr\\ArrowFunction' => 'Expr\\Arrow_Function',
    'Scalar\\MagicConst' => 'Scalar\\Magic_Const',
    'Scalar\\DNumber' => 'Scalar\\D_Number',
    'Scalar\\LNumber' => 'Scalar\\L_Number',
    'Scalar\\Encapsed' => 'Scalar\\Encapsed',
    'Scalar\\String_' => 'Scalar\\String_',
    'MagicConst\\' => 'Magic_Const\\',
    'NodeVisitor\\NameResolver' => 'Node_Visitor\\Name_Resolver',
    'NodeVisitor\\FindingVisitor' => 'Node_Visitor\\Finding_Visitor',
    'NodeVisitor\\FirstFindingVisitor' => 'Node_Visitor\\First_Finding_Visitor',
    'NodeVisitor\\NodeConnectingVisitor' => 'Node_Visitor\\Node_Connecting_Visitor',
    'NodeVisitor\\ParentConnectingVisitor' => 'Node_Visitor\\Parent_Connecting_Visitor',
    'NodeVisitor\\CloningVisitor' => 'Node_Visitor\\Cloning_Visitor',
    'NodeVisitor\\CommentAnnotatingVisitor' => 'Node_Visitor\\Comment_Annotating_Visitor',
    'ErrorHandler\\' => 'Error_Handler\\',
    'PrettyPrinter\\' => 'Pretty_Printer\\',
    'classConst' => 'class_const',
    'createClassConstBuilder' => 'create_class_const_builder',
    'testFactoryClassConst' => 'test_factory_class_const',
    'testPrettyPrintWithErrorInClassConstFetch' => 'test_pretty_print_with_error_in_class_const_fetch',
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $fileInfo) {
    if (substr($fileInfo->getFilename(), -4) !== '.php') {
        continue;
    }
    $path = $fileInfo->getPathname();
    $content = file_get_contents($path);
    $original = $content;
    foreach ($classReplacements as $from => $to) {
        $content = str_replace($from, $to, $content);
    }
    if ($content !== $original) {
        file_put_contents($path, $content);
    }
}

fwrite(STDOUT, "fix-tests-classnames: done.\n");
