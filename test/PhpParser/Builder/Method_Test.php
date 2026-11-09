<?php

declare(strict_types=1);

namespace Php_Parser\Builder;

use Php_Parser\Comment;
use Php_Parser\Modifiers;
use Php_Parser\Node;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Attribute;
use Php_Parser\Node\Attribute_Group;
use Php_Parser\Node\Expr\Print_;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Scalar\String_;
use Php_Parser\Node\Stmt;

class Method_Test extends \PHPUnit\Framework\TestCase
{
    public function createMethodBuilder($name)
    {
        return new Method($name);
    }

    public function testModifiers(): void
    {
        $node = $this->createMethodBuilder('test')
            ->make_public()
            ->make_abstract()
            ->make_static()
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Class_Method('test', [
                'flags' => Modifiers::PUBLIC | Modifiers::ABSTRACT | Modifiers::STATIC,
                'stmts' => null,
            ]),
            $node
        );

        $node = $this->createMethodBuilder('test')
            ->make_protected()
            ->make_final()
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Class_Method('test', [
                'flags' => Modifiers::PROTECTED | Modifiers::FINAL,
            ]),
            $node
        );

        $node = $this->createMethodBuilder('test')
            ->make_private()
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Class_Method('test', [
                'type' => Modifiers::PRIVATE,
            ]),
            $node
        );
    }

    public function testReturnByRef(): void
    {
        $node = $this->createMethodBuilder('test')
            ->make_return_by_ref()
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Class_Method('test', [
                'by_ref' => true,
            ]),
            $node
        );
    }

    public function testParams(): void
    {
        $param1 = new Node\Param(new Variable('test1'));
        $param2 = new Node\Param(new Variable('test2'));
        $param3 = new Node\Param(new Variable('test3'));

        $node = $this->createMethodBuilder('test')
            ->add_param($param1)
            ->add_params([$param2, $param3])
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Class_Method('test', [
                'params' => [$param1, $param2, $param3],
            ]),
            $node
        );
    }

    public function testStmts(): void
    {
        $stmt1 = new Print_(new String_('test1'));
        $stmt2 = new Print_(new String_('test2'));
        $stmt3 = new Print_(new String_('test3'));

        $node = $this->createMethodBuilder('test')
            ->add_stmt($stmt1)
            ->add_stmts([$stmt2, $stmt3])
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Class_Method('test', [
                'stmts' => [
                    new Stmt\Expression($stmt1),
                    new Stmt\Expression($stmt2),
                    new Stmt\Expression($stmt3),
                ],
            ]),
            $node
        );
    }
    public function testDocComment(): void
    {
        $node = $this->createMethodBuilder('test')
            ->set_doc_comment('/** Test */')
            ->get_node();

        $this->assertEquals(new Stmt\Class_Method('test', [], [
            'comments' => [new Comment\Doc('/** Test */')],
        ]), $node);
    }

    public function testAddAttribute(): void
    {
        $attribute = new Attribute(
            new Name('Attr'),
            [new Arg(new Int_(1), false, false, [], new Identifier('name'))]
        );
        $attributeGroup = new Attribute_Group([$attribute]);

        $node = $this->createMethodBuilder('attributeGroup')
            ->add_attribute($attributeGroup)
            ->get_node();

        $this->assertEquals(new Stmt\Class_Method('attributeGroup', [
            'attr_groups' => [$attributeGroup],
        ], []), $node);
    }

    public function testReturnType(): void
    {
        $node = $this->createMethodBuilder('test')
            ->set_return_type('bool')
            ->get_node();
        $this->assertEquals(new Stmt\Class_Method('test', [
            'return_type' => new Identifier('bool'),
        ], []), $node);
    }

    public function testAddStmtToAbstractMethodError(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot add statements to an abstract method');
        $this->createMethodBuilder('test')
            ->make_abstract()
            ->add_stmt(new Print_(new String_('test')))
        ;
    }

    public function testMakeMethodWithStmtsAbstractError(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot make method with statements abstract');
        $this->createMethodBuilder('test')
            ->add_stmt(new Print_(new String_('test')))
            ->make_abstract()
        ;
    }

    public function testInvalidParamError(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected parameter node, got "Name"');
        $this->createMethodBuilder('test')
            ->add_param(new Node\Name('foo'))
        ;
    }
}
