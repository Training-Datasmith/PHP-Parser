<?php

declare(strict_types=1);

namespace Php_Parser\Builder;

use Php_Parser\Comment;
use Php_Parser\Modifiers;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Attribute;
use Php_Parser\Node\Attribute_Group;
use Php_Parser\Node\Const_;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Property_Item;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Stmt;
use Php_Parser\Node\Stmt\Class_Const;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Property;
use Php_Parser\Node\Stmt\Trait_Use;

class Trait_Test extends \PHPUnit\Framework\TestCase
{
    protected function createTraitBuilder($class)
    {
        return new Trait_($class);
    }

    public function testStmtAddition(): void
    {
        $method1 = new Stmt\Class_Method('test1');
        $method2 = new Stmt\Class_Method('test2');
        $method3 = new Stmt\Class_Method('test3');
        $prop = new Stmt\Property(Modifiers::PUBLIC, [
            new Property_Item('test'),
        ]);
        $const = new Class_Const([new Const_('FOO', new Int_(0))]);
        $use = new Stmt\Trait_Use([new Name('OtherTrait')]);
        $trait = $this->createTraitBuilder('TestTrait')
            ->set_doc_comment('/** Nice trait */')
            ->add_stmt($method1)
            ->add_stmts([$method2, $method3])
            ->add_stmt($prop)
            ->add_stmt($use)
            ->add_stmt($const)
            ->get_node();
        $this->assertEquals(new Stmt\Trait_('TestTrait', [
            'stmts' => [$use, $const, $prop, $method1, $method2, $method3],
        ], [
            'comments' => [
                new Comment\Doc('/** Nice trait */'),
            ],
        ]), $trait);
    }

    public function testInvalidStmtError(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unexpected node of type "Stmt_Echo"');
        $this->createTraitBuilder('Test')
            ->add_stmt(new Stmt\Echo_([]))
        ;
    }

    public function testGetMethods(): void
    {
        $methods = [
            new Class_Method('foo'),
            new Class_Method('bar'),
            new Class_Method('fooBar'),
        ];
        $trait = new Stmt\Trait_('Foo', [
            'stmts' => [
                new Trait_Use([]),
                $methods[0],
                new Class_Const([]),
                $methods[1],
                new Property(0, []),
                $methods[2],
            ],
        ]);

        $this->assertSame($methods, $trait->get_methods());
    }

    public function testGetProperties(): void
    {
        $properties = [
            new Property(Modifiers::PUBLIC, [new Property_Item('foo')]),
            new Property(Modifiers::PUBLIC, [new Property_Item('bar')]),
        ];
        $trait = new Stmt\Trait_('Foo', [
            'stmts' => [
                new Trait_Use([]),
                $properties[0],
                new Class_Const([]),
                $properties[1],
                new Class_Method('fooBar'),
            ],
        ]);

        $this->assertSame($properties, $trait->get_properties());
    }

    public function testAddAttribute(): void
    {
        $attribute = new Attribute(
            new Name('Attr'),
            [new Arg(new Int_(1), false, false, [], new Identifier('name'))]
        );
        $attributeGroup = new Attribute_Group([$attribute]);

        $node = $this->createTraitBuilder('Attribute_Group')
            ->add_attribute($attributeGroup)
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Trait_(
                'Attribute_Group',
                [
                    'attrGroups' => [$attributeGroup],
                ]
            ),
            $node
        );
    }
}
