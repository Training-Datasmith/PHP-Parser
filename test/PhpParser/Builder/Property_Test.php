<?php

declare(strict_types=1);

namespace Php_Parser\Builder;

use Php_Parser\Comment;
use Php_Parser\Error;
use Php_Parser\Modifiers;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Attribute;
use Php_Parser\Node\Attribute_Group;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Property_Hook;
use Php_Parser\Node\Property_Item;
use Php_Parser\Node\Scalar;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Stmt;

class Property_Test extends \PHPUnit\Framework\TestCase
{
    public function createPropertyBuilder($name)
    {
        return new Property($name);
    }

    public function testModifiers(): void
    {
        $node = $this->createPropertyBuilder('test')
            ->make_private()
            ->make_static()
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Property(
                Modifiers::PRIVATE | Modifiers::STATIC,
                [new Property_Item('test')]
            ),
            $node
        );

        $node = $this->createPropertyBuilder('test')
            ->make_protected()
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Property(
                Modifiers::PROTECTED,
                [new Property_Item('test')]
            ),
            $node
        );

        $node = $this->createPropertyBuilder('test')
            ->make_public()
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Property(
                Modifiers::PUBLIC,
                [new Property_Item('test')]
            ),
            $node
        );

        $node = $this->createPropertyBuilder('test')
            ->make_readonly()
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Property(
                Modifiers::READONLY,
                [new Property_Item('test')]
            ),
            $node
        );

        $node = $this->createPropertyBuilder('test')
            ->make_final()
            ->get_node();
        $this->assertEquals(
            new Stmt\Property(Modifiers::FINAL, [new Property_Item('test')]),
            $node
        );

        $node = $this->createPropertyBuilder('test')
            ->make_private_set()
            ->get_node();
        $this->assertEquals(
            new Stmt\Property(Modifiers::PRIVATE_SET, [new Property_Item('test')]),
            $node
        );

        $node = $this->createPropertyBuilder('test')
            ->make_protected_set()
            ->get_node();
        $this->assertEquals(
            new Stmt\Property(Modifiers::PROTECTED_SET, [new Property_Item('test')]),
            $node
        );
    }

    public function testAbstractWithoutHook()
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage('Only hooked properties may be declared abstract');
        $this->createPropertyBuilder('test')->make_abstract()->get_node();
    }

    public function testDocComment(): void
    {
        $node = $this->createPropertyBuilder('test')
            ->set_doc_comment('/** Test */')
            ->get_node();

        $this->assertEquals(new Stmt\Property(
            Modifiers::PUBLIC,
            [
                new \Php_Parser\Node\Property_Item('test'),
            ],
            [
                'comments' => [new Comment\Doc('/** Test */')],
            ]
        ), $node);
    }

    /**
     * @dataProvider provideTestDefaultValues
     */
    public function testDefaultValues($value, $expectedValueNode): void
    {
        $node = $this->createPropertyBuilder('test')
            ->set_default($value)
            ->get_node()
        ;

        $this->assertEquals($expectedValueNode, $node->props[0]->default);
    }

    public function testAddAttribute(): void
    {
        $attribute = new Attribute(
            new Name('Attr'),
            [new Arg(new Int_(1), false, false, [], new Identifier('name'))]
        );
        $attributeGroup = new Attribute_Group([$attribute]);

        $node = $this->createPropertyBuilder('test')
            ->add_attribute($attributeGroup)
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Property(
                Modifiers::PUBLIC,
                [
                    new \Php_Parser\Node\Property_Item('test'),
                ],
                [],
                null,
                [$attributeGroup]
            ),
            $node
        );
    }

    public function testAddHook(): void
    {
        $get = new Property_Hook('get', null);
        $set = new Property_Hook('set', null);
        $node = $this->createPropertyBuilder('test')
            ->add_hook($get)
            ->add_hook($set)
            ->make_abstract()
            ->get_node();
        $this->assertEquals(
            new Stmt\Property(
                Modifiers::ABSTRACT,
                [new Property_Item('test')],
                [],
                null,
                [],
                [$get, $set]
            ),
            $node
        );
    }

    public static function provideTestDefaultValues()
    {
        return [
            [
                null,
                new Expr\Const_Fetch(new Name('null')),
            ],
            [
                true,
                new Expr\Const_Fetch(new Name('true')),
            ],
            [
                false,
                new Expr\Const_Fetch(new Name('false')),
            ],
            [
                31415,
                new Scalar\Int_(31415),
            ],
            [
                3.1415,
                new Scalar\Float_(3.1415),
            ],
            [
                'Hallo World',
                new Scalar\String_('Hallo World'),
            ],
            [
                [1, 2, 3],
                new Expr\Array_([
                    new \Php_Parser\Node\Array_Item(new Scalar\Int_(1)),
                    new \Php_Parser\Node\Array_Item(new Scalar\Int_(2)),
                    new \Php_Parser\Node\Array_Item(new Scalar\Int_(3)),
                ]),
            ],
            [
                ['foo' => 'bar', 'bar' => 'foo'],
                new Expr\Array_([
                    new \Php_Parser\Node\Array_Item(
                        new Scalar\String_('bar'),
                        new Scalar\String_('foo')
                    ),
                    new \Php_Parser\Node\Array_Item(
                        new Scalar\String_('foo'),
                        new Scalar\String_('bar')
                    ),
                ]),
            ],
            [
                new Scalar\Magic_Const\Dir(),
                new Scalar\Magic_Const\Dir(),
            ],
        ];
    }
}
