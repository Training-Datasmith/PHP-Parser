<?php

declare(strict_types=1);

namespace Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node_Traverser;

class First_Finding_Visitor_Test extends \PHPUnit\Framework\TestCase
{
    public function testFindFirstVariable(): void
    {
        $traverser = new Node_Traverser();
        $visitor = new First_Finding_Visitor(function (Node $node) {
            return $node instanceof Node\Expr\Variable;
        });
        $traverser->add_visitor($visitor);

        $assign = new Expr\Assign(new Expr\Variable('a'), new Expr\Variable('b'));
        $stmts = [new Node\Stmt\Expression($assign)];

        $traverser->traverse($stmts);
        $this->assertSame($assign->var, $visitor->get_found_node());
    }

    public function testFindNone(): void
    {
        $traverser = new Node_Traverser();
        $visitor = new First_Finding_Visitor(function (Node $node) {
            return $node instanceof Node\Expr\BinaryOp;
        });
        $traverser->add_visitor($visitor);

        $assign = new Expr\Assign(new Expr\Variable('a'), new Expr\Variable('b'));
        $stmts = [new Node\Stmt\Expression($assign)];

        $traverser->traverse($stmts);
        $this->assertNull($visitor->get_found_node());
    }
}
