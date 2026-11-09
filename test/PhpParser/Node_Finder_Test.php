<?php

declare(strict_types=1);

namespace Php_Parser;

use Php_Parser\Node\Expr;

class Node_Finder_Test extends \PHPUnit\Framework\TestCase
{
    private function getStmtsAndVars()
    {
        $assign = new Expr\Assign(new Expr\Variable('a'), new Expr\Binary_Op\Concat(
            new Expr\Variable('b'),
            new Expr\Variable('c')
        ));
        $stmts = [new Node\Stmt\Expression($assign)];
        $vars = [$assign->var, $assign->expr->left, $assign->expr->right];
        return [$stmts, $vars];
    }

    public function testFind(): void
    {
        $finder = new Node_Finder();
        list($stmts, $vars) = $this->getStmtsAndVars();
        $varFilter = function (Node $node) {
            return $node instanceof Expr\Variable;
        };
        $this->assertSame($vars, $finder->find($stmts, $varFilter));
        $this->assertSame($vars, $finder->find($stmts[0], $varFilter));

        $noneFilter = function () {
            return false;
        };
        $this->assertSame([], $finder->find($stmts, $noneFilter));
    }

    public function testFindInstanceOf(): void
    {
        $finder = new Node_Finder();
        list($stmts, $vars) = $this->getStmtsAndVars();
        $this->assertSame($vars, $finder->find_instance_of($stmts, Expr\Variable::class));
        $this->assertSame($vars, $finder->find_instance_of($stmts[0], Expr\Variable::class));
        $this->assertSame([], $finder->find_instance_of($stmts, Expr\Binary_Op\Mul::class));
    }

    public function testFindFirst(): void
    {
        $finder = new Node_Finder();
        list($stmts, $vars) = $this->getStmtsAndVars();
        $varFilter = function (Node $node) {
            return $node instanceof Expr\Variable;
        };
        $this->assertSame($vars[0], $finder->find_first($stmts, $varFilter));
        $this->assertSame($vars[0], $finder->find_first($stmts[0], $varFilter));

        $noneFilter = function () {
            return false;
        };
        $this->assertNull($finder->find_first($stmts, $noneFilter));
    }

    public function testFindFirstInstanceOf(): void
    {
        $finder = new Node_Finder();
        list($stmts, $vars) = $this->getStmtsAndVars();
        $this->assertSame($vars[0], $finder->find_first_instance_of($stmts, Expr\Variable::class));
        $this->assertSame($vars[0], $finder->find_first_instance_of($stmts[0], Expr\Variable::class));
        $this->assertNull($finder->find_first_instance_of($stmts, Expr\Binary_Op\Mul::class));
    }
}
