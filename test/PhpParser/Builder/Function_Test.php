<?php

declare(strict_types=1);

namespace Php_Parser\Builder;

use Php_Parser\Comment;
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

class Function_Test extends \PHPUnit\Framework\TestCase
{
    public function createFunctionBuilder($name)
    {
        return new Function_($name);
    }

    public function testReturnByRef(): void
    {
        $node = $this->createFunctionBuilder('test')
            ->make_return_by_ref()
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Function_('test', [
                'byRef' => true,
            ]),
            $node
        );
    }

    public function testParams(): void
    {
        $param1 = new Node\Param(new Variable('test1'));
        $param2 = new Node\Param(new Variable('test2'));
        $param3 = new Node\Param(new Variable('test3'));

        $node = $this->createFunctionBuilder('test')
            ->add_param($param1)
            ->add_params([$param2, $param3])
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Function_('test', [
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

        $node = $this->createFunctionBuilder('test')
            ->add_stmt($stmt1)
            ->add_stmts([$stmt2, $stmt3])
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Function_('test', [
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
        $node = $this->createFunctionBuilder('test')
            ->set_doc_comment('/** Test */')
            ->get_node();

        $this->assertEquals(new Stmt\Function_('test', [], [
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

        $node = $this->createFunctionBuilder('attrGroup')
            ->add_attribute($attributeGroup)
            ->get_node();

        $this->assertEquals(new Stmt\Function_('attrGroup', [
            'attrGroups' => [$attributeGroup],
        ], []), $node);
    }

    public function testReturnType(): void
    {
        $node = $this->createFunctionBuilder('test')
            ->set_return_type('void')
            ->get_node();

        $this->assertEquals(new Stmt\Function_('test', [
            'returnType' => new Identifier('void'),
        ], []), $node);
    }

    public function testInvalidNullableVoidType(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('void type cannot be nullable');
        $this->createFunctionBuilder('test')->set_return_type('?void');
    }

    public function testInvalidParamError(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected parameter node, got "Name"');
        $this->createFunctionBuilder('test')
            ->add_param(new Node\Name('foo'))
        ;
    }

    public function testAddNonStmt(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected statement or expression node');
        $this->createFunctionBuilder('test')
            ->add_stmt(new Node\Name('Test'));
    }
}
