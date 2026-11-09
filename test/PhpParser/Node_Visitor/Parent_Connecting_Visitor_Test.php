<?php

declare(strict_types=1);

namespace Php_Parser\Node_Visitor;

use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node_Finder;
use Php_Parser\Node_Traverser;
use Php_Parser\Parser_Factory;

final class Parent_Connecting_Visitor_Test extends \PHPUnit\Framework\TestCase
{
    public function testConnectsChildNodeToParentNode(): void
    {
        $ast = (new Parser_Factory())->create_for_newest_supported_version()->parse(
            '<?php class C { public function m() {} }'
        );

        $traverser = new Node_Traverser();

        $traverser->add_visitor(new Parent_Connecting_Visitor());

        $ast = $traverser->traverse($ast);

        $node = (new Node_Finder())->find_first_instance_of($ast, Class_Method::class);

        $this->assertSame('C', $node->get_attribute('parent')->name->to_string());
    }

    public function testWeakReferences(): void
    {
        $ast = (new Parser_Factory())->create_for_newest_supported_version()->parse(
            '<?php class C { public function m() {} }'
        );

        $traverser = new Node_Traverser();

        $traverser->add_visitor(new Parent_Connecting_Visitor(true));

        $ast = $traverser->traverse($ast);

        $node = (new Node_Finder())->find_first_instance_of($ast, Class_Method::class);

        $weakReference = $node->get_attribute('weak_parent');
        $this->assertInstanceOf(\WeakReference::class, $weakReference);
        $this->assertSame('C', $weakReference->get()->name->to_string());
    }
}
