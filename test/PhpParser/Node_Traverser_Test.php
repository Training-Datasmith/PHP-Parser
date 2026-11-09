<?php

declare(strict_types=1);

namespace Php_Parser;

use Php_Parser\Node\Expr;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Scalar\String_;
use Php_Parser\Node\Stmt\Else_;
use Php_Parser\Node\Stmt\If_;

class Node_Traverser_Test extends \PHPUnit\Framework\TestCase
{
    public function testNonModifying(): void
    {
        $str1Node = new String_('Foo');
        $str2Node = new String_('Bar');
        $echoNode = new Node\Stmt\Echo_([$str1Node, $str2Node]);
        $stmts    = [$echoNode];

        $visitor = new Node_Visitor_For_Testing();
        $traverser = new Node_Traverser();
        $traverser->add_visitor($visitor);

        $this->assertEquals($stmts, $traverser->traverse($stmts));
        $this->assertEquals([
            ['before_traverse', $stmts],
            ['enter_node', $echoNode],
            ['enter_node', $str1Node],
            ['leave_node', $str1Node],
            ['enter_node', $str2Node],
            ['leave_node', $str2Node],
            ['leave_node', $echoNode],
            ['after_traverse', $stmts],
        ], $visitor->trace);
    }

    public function testModifying(): void
    {
        $str1Node  = new String_('Foo');
        $str2Node  = new String_('Bar');
        $printNode = new Expr\Print_($str1Node);

        // Visitor 2 performs changes, visitors 1 and 3 observe the changes.
        $visitor1 = new Node_Visitor_For_Testing();
        $visitor2 = new Node_Visitor_For_Testing([
            ['before_traverse', [], [$str1Node]],
            ['enter_node', $str1Node, $printNode],
            ['enter_node', $str1Node, $str2Node],
            ['leave_node', $str2Node, $str1Node],
            ['leave_node', $printNode, $str1Node],
            ['after_traverse', [$str1Node], []],
        ]);
        $visitor3 = new Node_Visitor_For_Testing();

        $traverser = new Node_Traverser($visitor1, $visitor2, $visitor3);

        // as all operations are reversed we end where we start
        $this->assertEquals([], $traverser->traverse([]));
        $this->assertEquals([
            // Sees nodes before changes on entry.
            ['before_traverse', []],
            ['enter_node', $str1Node],
            ['enter_node', $str1Node],
            // Sees nodes after changes on leave.
            ['leave_node', $str1Node],
            ['leave_node', $str1Node],
            ['after_traverse', []],
        ], $visitor1->trace);
        $this->assertEquals([
            // Sees nodes after changes on entry.
            ['before_traverse', [$str1Node]],
            ['enter_node', $printNode],
            ['enter_node', $str2Node],
            // Sees nodes before changes on leave.
            ['leave_node', $str2Node],
            ['leave_node', $printNode],
            ['after_traverse', [$str1Node]],
        ], $visitor3->trace);
    }

    public function testRemoveFromLeave(): void
    {
        $str1Node = new String_('Foo');
        $str2Node = new String_('Bar');

        $visitor = new Node_Visitor_For_Testing([
            ['leave_node', $str1Node, Node_Visitor::REMOVE_NODE],
        ]);
        $visitor2 = new Node_Visitor_For_Testing();

        $traverser = new Node_Traverser();
        $traverser->add_visitor($visitor2);
        $traverser->add_visitor($visitor);

        $stmts = [$str1Node, $str2Node];
        $this->assertEquals([$str2Node], $traverser->traverse($stmts));
        $this->assertEquals([
            ['before_traverse', $stmts],
            ['enter_node', $str1Node],
            ['enter_node', $str2Node],
            ['leave_node', $str2Node],
            ['after_traverse', [$str2Node]],
        ], $visitor2->trace);
    }

    public function testRemoveFromEnter(): void
    {
        $str1Node = new String_('Foo');
        $str2Node = new String_('Bar');

        $visitor = new Node_Visitor_For_Testing([
            ['enter_node', $str1Node, Node_Visitor::REMOVE_NODE],
        ]);
        $visitor2 = new Node_Visitor_For_Testing();

        $traverser = new Node_Traverser();
        $traverser->add_visitor($visitor);
        $traverser->add_visitor($visitor2);

        $stmts = [$str1Node, $str2Node];
        $this->assertEquals([$str2Node], $traverser->traverse($stmts));
        $this->assertEquals([
            ['before_traverse', $stmts],
            ['enter_node', $str2Node],
            ['leave_node', $str2Node],
            ['after_traverse', [$str2Node]],
        ], $visitor2->trace);
    }

    public function testReturnArrayFromEnter(): void
    {
        $str1Node = new String_('Str1');
        $str2Node = new String_('Str2');
        $str3Node = new String_('Str3');
        $str4Node = new String_('Str4');

        $visitor = new Node_Visitor_For_Testing([
            ['enter_node', $str1Node, [$str3Node, $str4Node]],
        ]);
        $visitor2 = new Node_Visitor_For_Testing();

        $traverser = new Node_Traverser();
        $traverser->add_visitor($visitor);
        $traverser->add_visitor($visitor2);

        $stmts = [$str1Node, $str2Node];
        $this->assertEquals([$str3Node, $str4Node, $str2Node], $traverser->traverse($stmts));
        $this->assertEquals([
            ['before_traverse', $stmts],
            ['enter_node', $str2Node],
            ['leave_node', $str2Node],
            ['after_traverse', [$str3Node, $str4Node, $str2Node]],
        ], $visitor2->trace);
    }

    public function testMerge(): void
    {
        $strStart  = new String_('Start');
        $strMiddle = new String_('End');
        $strEnd    = new String_('Middle');
        $strR1     = new String_('Replacement 1');
        $strR2     = new String_('Replacement 2');

        $visitor = new Node_Visitor_For_Testing([
            ['leave_node', $strMiddle, [$strR1, $strR2]],
        ]);

        $traverser = new Node_Traverser();
        $traverser->add_visitor($visitor);

        $this->assertEquals(
            [$strStart, $strR1, $strR2, $strEnd],
            $traverser->traverse([$strStart, $strMiddle, $strEnd])
        );
    }

    public function testInvalidDeepArray(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid node structure: Contains nested arrays');
        $strNode = new String_('Foo');
        $stmts = [[[$strNode]]];

        $traverser = new Node_Traverser();
        $this->assertEquals($stmts, $traverser->traverse($stmts));
    }

    public function testDontTraverseChildren(): void
    {
        $strNode = new String_('str');
        $printNode = new Expr\Print_($strNode);
        $varNode = new Expr\Variable('foo');
        $mulNode = new Expr\Binary_Op\Mul($varNode, $varNode);
        $negNode = new Expr\Unary_Minus($mulNode);
        $stmts = [$printNode, $negNode];

        $visitor1 = new Node_Visitor_For_Testing([
            ['enter_node', $printNode, Node_Visitor::DONT_TRAVERSE_CHILDREN],
        ]);
        $visitor2 = new Node_Visitor_For_Testing([
            ['enter_node', $mulNode, Node_Visitor::DONT_TRAVERSE_CHILDREN],
        ]);

        $expectedTrace = [
            ['before_traverse', $stmts],
            ['enter_node', $printNode],
            ['leave_node', $printNode],
            ['enter_node', $negNode],
            ['enter_node', $mulNode],
            ['leave_node', $mulNode],
            ['leave_node', $negNode],
            ['after_traverse', $stmts],
        ];

        $traverser = new Node_Traverser();
        $traverser->add_visitor($visitor1);
        $traverser->add_visitor($visitor2);

        $this->assertEquals($stmts, $traverser->traverse($stmts));
        $this->assertEquals($expectedTrace, $visitor1->trace);
        $this->assertEquals($expectedTrace, $visitor2->trace);
    }

    public function testDontTraverseCurrentAndChildren(): void
    {
        // print 'str'; -($foo * $foo);
        $strNode = new String_('str');
        $printNode = new Expr\Print_($strNode);
        $varNode = new Expr\Variable('foo');
        $mulNode = new Expr\Binary_Op\Mul($varNode, $varNode);
        $divNode = new Expr\Binary_Op\Div($varNode, $varNode);
        $negNode = new Expr\Unary_Minus($mulNode);
        $stmts = [$printNode, $negNode];

        $visitor1 = new Node_Visitor_For_Testing([
            ['enter_node', $printNode, Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN],
            ['enter_node', $mulNode, Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN],
            ['leave_node', $mulNode, $divNode],
        ]);
        $visitor2 = new Node_Visitor_For_Testing();

        $traverser = new Node_Traverser();
        $traverser->add_visitor($visitor1);
        $traverser->add_visitor($visitor2);

        $resultStmts = $traverser->traverse($stmts);
        $this->assertInstanceOf(Expr\Binary_Op\Div::class, $resultStmts[1]->expr);

        $this->assertEquals([
            ['before_traverse', $stmts],
            ['enter_node', $printNode],
            ['leave_node', $printNode],
            ['enter_node', $negNode],
            ['enter_node', $mulNode],
            ['leave_node', $mulNode],
            ['leave_node', $negNode],
            ['after_traverse', $resultStmts],
        ], $visitor1->trace);
        $this->assertEquals([
            ['before_traverse', $stmts],
            ['enter_node', $negNode],
            ['leave_node', $negNode],
            ['after_traverse', $resultStmts],
        ], $visitor2->trace);
    }

    public function testStopTraversal(): void
    {
        $varNode1 = new Expr\Variable('a');
        $varNode2 = new Expr\Variable('b');
        $varNode3 = new Expr\Variable('c');
        $mulNode = new Expr\Binary_Op\Mul($varNode1, $varNode2);
        $printNode = new Expr\Print_($varNode3);
        $stmts = [$mulNode, $printNode];

        // From enterNode() with array parent
        $visitor = new Node_Visitor_For_Testing([
            ['enter_node', $mulNode, Node_Visitor::STOP_TRAVERSAL],
        ]);
        $traverser = new Node_Traverser();
        $traverser->add_visitor($visitor);
        $this->assertEquals($stmts, $traverser->traverse($stmts));
        $this->assertEquals([
            ['before_traverse', $stmts],
            ['enter_node', $mulNode],
            ['after_traverse', $stmts],
        ], $visitor->trace);

        // From enterNode with Node parent
        $visitor = new Node_Visitor_For_Testing([
            ['enter_node', $varNode1, Node_Visitor::STOP_TRAVERSAL],
        ]);
        $traverser = new Node_Traverser();
        $traverser->add_visitor($visitor);
        $this->assertEquals($stmts, $traverser->traverse($stmts));
        $this->assertEquals([
            ['before_traverse', $stmts],
            ['enter_node', $mulNode],
            ['enter_node', $varNode1],
            ['after_traverse', $stmts],
        ], $visitor->trace);

        // From leaveNode with Node parent
        $visitor = new Node_Visitor_For_Testing([
            ['leave_node', $varNode1, Node_Visitor::STOP_TRAVERSAL],
        ]);
        $traverser = new Node_Traverser();
        $traverser->add_visitor($visitor);
        $this->assertEquals($stmts, $traverser->traverse($stmts));
        $this->assertEquals([
            ['before_traverse', $stmts],
            ['enter_node', $mulNode],
            ['enter_node', $varNode1],
            ['leave_node', $varNode1],
            ['after_traverse', $stmts],
        ], $visitor->trace);

        // From leaveNode with array parent
        $visitor = new Node_Visitor_For_Testing([
            ['leave_node', $mulNode, Node_Visitor::STOP_TRAVERSAL],
        ]);
        $traverser = new Node_Traverser();
        $traverser->add_visitor($visitor);
        $this->assertEquals($stmts, $traverser->traverse($stmts));
        $this->assertEquals([
            ['before_traverse', $stmts],
            ['enter_node', $mulNode],
            ['enter_node', $varNode1],
            ['leave_node', $varNode1],
            ['enter_node', $varNode2],
            ['leave_node', $varNode2],
            ['leave_node', $mulNode],
            ['after_traverse', $stmts],
        ], $visitor->trace);

        // Check that pending array modifications are still carried out
        $visitor = new Node_Visitor_For_Testing([
            ['leave_node', $mulNode, Node_Visitor::REMOVE_NODE],
            ['enter_node', $printNode, Node_Visitor::STOP_TRAVERSAL],
        ]);
        $traverser = new Node_Traverser();
        $traverser->add_visitor($visitor);
        $this->assertEquals([$printNode], $traverser->traverse($stmts));
        $this->assertEquals([
            ['before_traverse', $stmts],
            ['enter_node', $mulNode],
            ['enter_node', $varNode1],
            ['leave_node', $varNode1],
            ['enter_node', $varNode2],
            ['leave_node', $varNode2],
            ['leave_node', $mulNode],
            ['enter_node', $printNode],
            ['after_traverse', [$printNode]],
        ], $visitor->trace);
    }

    public function testReplaceWithNull(): void
    {
        $one = new Int_(1);
        $else1 = new Else_();
        $else2 = new Else_();
        $if1 = new If_($one, ['else' => $else1]);
        $if2 = new If_($one, ['else' => $else2]);
        $stmts = [$if1, $if2];
        $visitor1 = new Node_Visitor_For_Testing([
            ['enter_node', $else1, Node_Visitor::REPLACE_WITH_NULL],
            ['leave_node', $else2, Node_Visitor::REPLACE_WITH_NULL],
        ]);
        $visitor2 = new Node_Visitor_For_Testing();
        $traverser = new Node_Traverser();
        $traverser->add_visitor($visitor1);
        $traverser->add_visitor($visitor2);
        $newStmts = $traverser->traverse($stmts);
        $this->assertEquals([
            new If_($one),
            new If_($one),
        ], $newStmts);
        $this->assertEquals([
            ['before_traverse', $stmts],
            ['enter_node', $if1],
            ['enter_node', $one],
            // We never see the if1 Else node.
            ['leave_node', $one],
            ['leave_node', $if1],
            ['enter_node', $if2],
            ['enter_node', $one],
            ['leave_node', $one],
            // We do see the if2 Else node, as it will only be replaced afterwards.
            ['enter_node', $else2],
            ['leave_node', $else2],
            ['leave_node', $if2],
            ['after_traverse', $stmts],
        ], $visitor2->trace);
    }

    public function testRemovingVisitor(): void
    {
        $visitor1 = new class () extends Node_Visitor_Abstract {};
        $visitor2 = new class () extends Node_Visitor_Abstract {};
        $visitor3 = new class () extends Node_Visitor_Abstract {};

        $traverser = new Node_Traverser();
        $traverser->add_visitor($visitor1);
        $traverser->add_visitor($visitor2);
        $traverser->add_visitor($visitor3);

        $getVisitors = (function () {
            return $this->visitors;
        })->bindTo($traverser, Node_Traverser::class);

        $preExpected = [$visitor1, $visitor2, $visitor3];
        $this->assertSame($preExpected, $getVisitors());

        $traverser->remove_visitor($visitor2);

        $postExpected = [$visitor1, $visitor3];
        $this->assertSame($postExpected, $getVisitors());
    }

    public function testNoCloneNodes(): void
    {
        $stmts = [new Node\Stmt\Echo_([new String_('Foo'), new String_('Bar')])];

        $traverser = new Node_Traverser();

        $this->assertSame($stmts, $traverser->traverse($stmts));
    }

    /**
     * @dataProvider provideTestInvalidReturn
     */
    public function testInvalidReturn($stmts, $visitor, $message): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage($message);

        $traverser = new Node_Traverser();
        $traverser->add_visitor($visitor);
        $traverser->traverse($stmts);
    }

    public static function provideTestInvalidReturn()
    {
        $num = new Node\Scalar\Int_(42);
        $expr = new Node\Stmt\Expression($num);
        $stmts = [$expr];

        $visitor1 = new Node_Visitor_For_Testing([
            ['enter_node', $expr, 'foobar'],
        ]);
        $visitor2 = new Node_Visitor_For_Testing([
            ['enter_node', $num, 'foobar'],
        ]);
        $visitor3 = new Node_Visitor_For_Testing([
            ['leave_node', $num, 'foobar'],
        ]);
        $visitor4 = new Node_Visitor_For_Testing([
            ['leave_node', $expr, 'foobar'],
        ]);
        $visitor5 = new Node_Visitor_For_Testing([
            ['leave_node', $num, [new Node\Scalar\Float_(42.0)]],
        ]);
        $visitor6 = new Node_Visitor_For_Testing([
            ['leave_node', $expr, false],
        ]);
        $visitor7 = new Node_Visitor_For_Testing([
            ['enter_node', $expr, new Node\Scalar\Int_(42)],
        ]);
        $visitor8 = new Node_Visitor_For_Testing([
            ['enter_node', $num, new Node\Stmt\Return_()],
        ]);
        $visitor9 = new Node_Visitor_For_Testing([
            ['enter_node', $expr, Node_Visitor::REPLACE_WITH_NULL],
        ]);
        $visitor10 = new Node_Visitor_For_Testing([
            ['leave_node', $expr, Node_Visitor::REPLACE_WITH_NULL],
        ]);

        return [
            [$stmts, $visitor1, 'enterNode() returned invalid value of type string'],
            [$stmts, $visitor2, 'enterNode() returned invalid value of type string'],
            [$stmts, $visitor3, 'leaveNode() returned invalid value of type string'],
            [$stmts, $visitor4, 'leaveNode() returned invalid value of type string'],
            [$stmts, $visitor5, 'leaveNode() may only return an array if the parent structure is an array'],
            [$stmts, $visitor6, 'leaveNode() returned invalid value of type bool'],
            [$stmts, $visitor7, 'Trying to replace statement (Stmt_Expression) with expression (Scalar_Int_). Are you missing a Stmt_Expression wrapper?'],
            [$stmts, $visitor8, 'Trying to replace expression (Scalar_Int_) with statement (Stmt_Return_)'],
            [$stmts, $visitor9, 'REPLACE_WITH_NULL can not be used if the parent structure is an array'],
            [$stmts, $visitor10, 'REPLACE_WITH_NULL can not be used if the parent structure is an array'],
        ];
    }
}
