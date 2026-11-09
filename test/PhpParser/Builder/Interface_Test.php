<?php

declare(strict_types=1);

namespace Php_Parser\Builder;

use Php_Parser\Comment;
use Php_Parser\Node;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Attribute;
use Php_Parser\Node\Attribute_Group;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Scalar\Float_;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Stmt;

class Interface_Test extends \PHPUnit\Framework\TestCase
{
    protected function createInterfaceBuilder()
    {
        return new Interface_('Contract');
    }

    private function dump($node)
    {
        $pp = new \Php_Parser\Pretty_Printer\Standard();
        return $pp->pretty_print([$node]);
    }

    public function testEmpty(): void
    {
        $contract = $this->createInterfaceBuilder()->get_node();
        $this->assertInstanceOf(Stmt\Interface_::class, $contract);
        $this->assertEquals(new Node\Identifier('Contract'), $contract->name);
    }

    public function testExtending(): void
    {
        $contract = $this->createInterfaceBuilder()
            ->extend('Space\Root1', 'Root2')->get_node();
        $this->assertEquals(
            new Stmt\Interface_('Contract', [
                'extends' => [
                    new Node\Name('Space\Root1'),
                    new Node\Name('Root2'),
                ],
            ]),
            $contract
        );
    }

    public function testAddMethod(): void
    {
        $method = new Stmt\Class_Method('doSomething');
        $contract = $this->createInterfaceBuilder()->add_stmt($method)->get_node();
        $this->assertSame([$method], $contract->stmts);
    }

    public function testAddConst(): void
    {
        $const = new Stmt\Class_Const([
            new Node\Const_('SPEED_OF_LIGHT', new Float_(299792458.0)),
        ]);
        $contract = $this->createInterfaceBuilder()->add_stmt($const)->get_node();
        $this->assertSame(299792458.0, $contract->stmts[0]->consts[0]->value->value);
    }

    public function testOrder(): void
    {
        $const = new Stmt\Class_Const([
            new Node\Const_('SPEED_OF_LIGHT', new Float_(299792458)),
        ]);
        $method = new Stmt\Class_Method('doSomething');
        $contract = $this->createInterfaceBuilder()
            ->add_stmt($method)
            ->add_stmt($const)
            ->get_node()
        ;

        $this->assertInstanceOf(Stmt\Class_Const::class, $contract->stmts[0]);
        $this->assertInstanceOf(Stmt\Class_Method::class, $contract->stmts[1]);
    }

    public function testDocComment(): void
    {
        $node = $this->createInterfaceBuilder()
            ->set_doc_comment('/** Test */')
            ->get_node();

        $this->assertEquals(new Stmt\Interface_('Contract', [], [
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

        $node = $this->createInterfaceBuilder()
            ->add_attribute($attributeGroup)
            ->get_node();

        $this->assertEquals(new Stmt\Interface_('Contract', [
            'attrGroups' => [$attributeGroup],
        ], []), $node);
    }

    public function testInvalidStmtError(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unexpected node of type "PropertyItem"');
        $this->createInterfaceBuilder()->add_stmt(new Node\Property_Item('invalid'));
    }

    public function testFullFunctional(): void
    {
        $const = new Stmt\Class_Const([
            new Node\Const_('SPEED_OF_LIGHT', new Float_(299792458)),
        ]);
        $method = new Stmt\Class_Method('doSomething');
        $contract = $this->createInterfaceBuilder()
            ->add_stmt($method)
            ->add_stmt($const)
            ->get_node()
        ;

        eval($this->dump($contract));

        $this->assertTrue(interface_exists('Contract', false));
    }
}
