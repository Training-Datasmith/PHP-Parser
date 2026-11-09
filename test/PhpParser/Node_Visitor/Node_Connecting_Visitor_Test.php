<?php

declare(strict_types=1);

namespace Php_Parser\Node_Visitor;

use Php_Parser\Node\Expr\Const_Fetch;
use Php_Parser\Node\Stmt\Else_;
use Php_Parser\Node\Stmt\If_;
use Php_Parser\Node_Finder;
use Php_Parser\Node_Traverser;
use Php_Parser\Parser_Factory;

final class Node_Connecting_Visitor_Test extends \PHPUnit\Framework\TestCase
{
    public function testConnectsNodeToItsParentNodeAndItsSiblingNodes(): void
    {
        $ast = (new Parser_Factory())->create_for_newest_supported_version()->parse(
            '<?php if (true) {} else {}'
        );

        $traverser = new Node_Traverser();

        $traverser->add_visitor(new Node_Connecting_Visitor());

        $ast = $traverser->traverse($ast);

        $node = (new Node_Finder())->find_first_instance_of($ast, Else_::class);

        $this->assertSame(If_::class, get_class($node->get_attribute('parent')));
        $this->assertSame(Const_Fetch::class, get_class($node->get_attribute('previous')));

        $node = (new Node_Finder())->find_first_instance_of($ast, Const_Fetch::class);

        $this->assertSame(Else_::class, get_class($node->get_attribute('next')));
    }

    public function testWeakReferences(): void
    {
        $ast = (new Parser_Factory())->create_for_newest_supported_version()->parse(
            '<?php if (true) {} else {}'
        );

        $traverser = new Node_Traverser();

        $traverser->add_visitor(new Node_Connecting_Visitor(true));

        $ast = $traverser->traverse($ast);

        $node = (new Node_Finder())->find_first_instance_of($ast, Else_::class);

        $this->assertInstanceOf(\WeakReference::class, $node->get_attribute('weak_parent'));
        $this->assertSame(If_::class, get_class($node->get_attribute('weak_parent')->get()));
        $this->assertInstanceOf(\WeakReference::class, $node->get_attribute('weak_previous'));
        $this->assertSame(Const_Fetch::class, get_class($node->get_attribute('weak_previous')->get()));

        $node = (new Node_Finder())->find_first_instance_of($ast, Const_Fetch::class);

        $this->assertInstanceOf(\WeakReference::class, $node->get_attribute('weak_next'));
        $this->assertSame(Else_::class, get_class($node->get_attribute('weak_next')->get()));
    }
}
