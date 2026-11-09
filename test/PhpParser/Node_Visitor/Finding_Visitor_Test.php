<?php

declare(strict_types=1);

namespace Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node_Traverser;

class Finding_Visitor_Test extends \PHPUnit\Framework\TestCase
{
    public function testFindVariables(): void
    {
        $traverser = new Node_Traverser();
        $visitor = new Finding_Visitor(function (Node $node) {
            return $node instanceof Node\Expr\Variable;
        });
        $traverser->add_visitor($visitor);

        $assign = new Expr\Assign(new Expr\Variable('a'), new Expr\Binary_Op\Concat(
            new Expr\Variable('b'),
            new Expr\Variable('c')
        ));
        $stmts = [new Node\Stmt\Expression($assign)];

        $traverser->traverse($stmts);
        $this->assertSame([
            $assign->var,
            $assign->expr->left,
            $assign->expr->right,
        ], $visitor->get_found_nodes());
    }

    public function testFindAll(): void
    {
        $traverser = new Node_Traverser();
        $visitor = new Finding_Visitor(function (Node $node) {
            return true; // All nodes
        });
        $traverser->add_visitor($visitor);

        $assign = new Expr\Assign(new Expr\Variable('a'), new Expr\Binary_Op\Concat(
            new Expr\Variable('b'),
            new Expr\Variable('c')
        ));
        $stmts = [new Node\Stmt\Expression($assign)];

        $traverser->traverse($stmts);
        $this->assertSame([
            $stmts[0],
            $assign,
            $assign->var,
            $assign->expr,
            $assign->expr->left,
            $assign->expr->right,
        ], $visitor->get_found_nodes());
    }
}
