<?php

declare(strict_types=1);

/**
 * Example 3: Generating PHP code programmatically using the Builder API.
 *
 * The Builder API lets you construct AST nodes without writing raw Node
 * objects by hand. The result is a valid PHP file that can be pretty-printed
 * to a string.
 *
 * Run from the repo root:
 *   php examples/03_code_generation.php
 */

require __DIR__ . '/../vendor/autoload.php';

use PhpParser\Builder\Class_;
use PhpParser\Builder\Method;
use PhpParser\Builder\Namespace_;
use PhpParser\Builder\Param;
use PhpParser\Builder\Property;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Return_;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;

$factory = new BuilderFactory();

// -----------------------------------------------------------------------
// 1. Build a namespace
// -----------------------------------------------------------------------

$namespace = $factory->namespace('App\Domain');

// -----------------------------------------------------------------------
// 2. Build a class with a constructor, a property, and a getter
// -----------------------------------------------------------------------

$class = $factory->class('Money')
    ->makeFinal()
    ->addStmt(
        // readonly property (PHP 8.1+)
        $factory->property('amountCents')
            ->makePrivate()
            ->makeReadonly()
            ->setType('int')
            ->setDocComment('/** @var int Amount in the smallest currency unit (e.g. cents) */')
    )
    ->addStmt(
        $factory->property('currency')
            ->makePrivate()
            ->makeReadonly()
            ->setType('string')
    )
    ->addStmt(
        // Constructor with promoted properties would look different;
        // here we show explicit constructor for clarity.
        $factory->method('__construct')
            ->makePublic()
            ->addParam(
                $factory->param('amountCents')->setType('int')
            )
            ->addParam(
                $factory->param('currency')->setType('string')
            )
            ->addStmt(
                new \PhpParser\Node\Expr\Assign(
                    new Variable('this->amountCents'),
                    new Variable('amountCents'),
                )
            )
            ->addStmt(
                new \PhpParser\Node\Expr\Assign(
                    new Variable('this->currency'),
                    new Variable('currency'),
                )
            )
    )
    ->addStmt(
        $factory->method('getAmountCents')
            ->makePublic()
            ->setReturnType('int')
            ->setDocComment('/** Returns the monetary amount in the smallest currency unit. */')
            ->addStmt(new Return_(new \PhpParser\Node\Expr\PropertyFetch(new Variable('this'), 'amountCents')))
    )
    ->addStmt(
        $factory->method('getCurrency')
            ->makePublic()
            ->setReturnType('string')
            ->setDocComment('/** Returns the ISO 4217 currency code. */')
            ->addStmt(new Return_(new \PhpParser\Node\Expr\PropertyFetch(new Variable('this'), 'currency')))
    )
    ->addStmt(
        $factory->method('add')
            ->makePublic()
            ->setReturnType('self')
            ->setDocComment('/** Returns a new Money instance with the combined amount. */')
            ->addParam($factory->param('other')->setType('self'))
            ->addStmt(
                new Return_(
                    new \PhpParser\Node\Expr\New_(
                        new \PhpParser\Node\Name('self'),
                        [
                            new \PhpParser\Node\Arg(
                                new \PhpParser\Node\Expr\BinaryOp\Plus(
                                    new \PhpParser\Node\Expr\PropertyFetch(new Variable('this'), 'amountCents'),
                                    new \PhpParser\Node\Expr\MethodCall(new Variable('other'), 'getAmountCents'),
                                )
                            ),
                            new \PhpParser\Node\Arg(
                                new \PhpParser\Node\Expr\PropertyFetch(new Variable('this'), 'currency')
                            ),
                        ]
                    )
                )
            )
    );

// -----------------------------------------------------------------------
// 3. Add the class to the namespace and get the AST
// -----------------------------------------------------------------------

$namespace->addStmt($class);
$stmts = [$namespace->getNode()];

// -----------------------------------------------------------------------
// 4. Pretty-print the generated code
// -----------------------------------------------------------------------

$printer = new PrettyPrinter();
$output  = $printer->prettyPrintFile($stmts);

echo "=== Generated PHP class ===\n";
echo $output . "\n";

// -----------------------------------------------------------------------
// 5. Verify it looks right — in a real tool you would write this to a file
// -----------------------------------------------------------------------

assert(str_contains($output, 'class Money'));
assert(str_contains($output, 'namespace App\Domain'));
assert(str_contains($output, 'getAmountCents'));

echo "\n=== Verification passed ===\n";
