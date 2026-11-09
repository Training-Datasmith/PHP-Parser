<?php

declare(strict_types=1);

namespace Php_Parser\Builder;

use Php_Parser\Comment;
use Php_Parser\Modifiers;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Attribute;
use Php_Parser\Node\Attribute_Group;
use Php_Parser\Node\Const_;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Scalar;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Stmt;

class Class_Const_Test extends \PHPUnit\Framework\TestCase
{
    public function createClassConst_Builder($name, $value)
    {
        return new Class_Const($name, $value);
    }

    public function testModifiers(): void
    {
        $node = $this->createClassConst_Builder('TEST', 1)
            ->make_private()
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Class_Const(
                [
                    new Const_('TEST', new Int_(1)),
                ],
                Modifiers::PRIVATE
            ),
            $node
        );

        $node = $this->createClassConst_Builder('TEST', 1)
            ->make_protected()
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Class_Const(
                [
                    new Const_('TEST', new Int_(1)),
                ],
                Modifiers::PROTECTED
            ),
            $node
        );

        $node = $this->createClassConst_Builder('TEST', 1)
            ->make_public()
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Class_Const(
                [
                    new Const_('TEST', new Int_(1)),
                ],
                Modifiers::PUBLIC
            ),
            $node
        );

        $node = $this->createClassConst_Builder('TEST', 1)
            ->make_final()
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Class_Const(
                [
                    new Const_('TEST', new Int_(1)),
                ],
                Modifiers::FINAL
            ),
            $node
        );
    }

    public function testDocComment(): void
    {
        $node = $this->createClassConst_Builder('TEST', 1)
            ->set_doc_comment('/** Test */')
            ->make_public()
            ->get_node();

        $this->assertEquals(
            new Stmt\Class_Const(
                [
                    new Const_('TEST', new Int_(1)),
                ],
                Modifiers::PUBLIC,
                [
                    'comments' => [new Comment\Doc('/** Test */')],
                ]
            ),
            $node
        );
    }

    public function testAddConst_(): void
    {
        $node = $this->createClassConst_Builder('FIRST_TEST', 1)
            ->add_const('SECOND_TEST', 2)
            ->get_node();

        $this->assertEquals(
            new Stmt\Class_Const(
                [
                    new Const_('FIRST_TEST', new Int_(1)),
                    new Const_('SECOND_TEST', new Int_(2)),
                ]
            ),
            $node
        );
    }

    public function testAddAttribute(): void
    {
        $attribute = new Attribute(
            new Name('Attr'),
            [new Arg(new Int_(1), false, false, [], new Identifier('name'))]
        );
        $attributeGroup = new Attribute_Group([$attribute]);

        $node = $this->createClassConst_Builder('ATTR_GROUP', 1)
            ->add_attribute($attributeGroup)
            ->get_node();

        $this->assertEquals(
            new Stmt\Class_Const(
                [
                    new Const_('ATTR_GROUP', new Int_(1)),
                ],
                0,
                [],
                [$attributeGroup]
            ),
            $node
        );
    }

    public function testType(): void
    {
        $node = $this->createClassConst_Builder('TYPE', 1)
            ->set_type('int')
            ->get_node();
        $this->assertEquals(
            new Stmt\Class_Const(
                [new Const_('TYPE', new Int_(1))],
                0,
                [],
                [],
                new Identifier('int')
            ),
            $node
        );
    }

    /**
     * @dataProvider provideTestDefaultValues
     */
    public function testValues($value, $expectedValueNode): void
    {
        $node = $this->createClassConst_Builder('TEST', $value)
            ->get_node()
        ;

        $this->assertEquals($expectedValueNode, $node->consts[0]->value);
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
