<?php

declare(strict_types=1);

namespace Php_Parser\Builder;

use Php_Parser\Comment\Doc;
use Php_Parser\Node;
use Php_Parser\Node\Stmt;

class Namespace_Test extends \PHPUnit\Framework\TestCase
{
    protected function createNamespaceBuilder($fqn)
    {
        return new Namespace_($fqn);
    }

    public function testCreation(): void
    {
        $stmt1 = new Stmt\Class_('SomeClass');
        $stmt2 = new Stmt\Interface_('SomeInterface');
        $stmt3 = new Stmt\Function_('someFunction');
        $docComment = new Doc('/** Test */');
        $expected = new Stmt\Namespace_(
            new Node\Name('Name\Space'),
            [$stmt1, $stmt2, $stmt3],
            ['comments' => [$docComment]]
        );

        $node = $this->createNamespaceBuilder('Name\Space')
            ->add_stmt($stmt1)
            ->add_stmts([$stmt2, $stmt3])
            ->set_doc_comment($docComment)
            ->get_node()
        ;
        $this->assertEquals($expected, $node);

        $node = $this->createNamespaceBuilder(new Node\Name(['Name', 'Space']))
            ->set_doc_comment($docComment)
            ->add_stmts([$stmt1, $stmt2])
            ->add_stmt($stmt3)
            ->get_node()
        ;
        $this->assertEquals($expected, $node);

        $node = $this->createNamespaceBuilder(null)->get_node();
        $this->assertNull($node->name);
        $this->assertEmpty($node->stmts);
    }
}
