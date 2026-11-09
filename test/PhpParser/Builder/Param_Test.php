<?php

declare(strict_types=1);

namespace Php_Parser\Builder;

use Php_Parser\Modifiers;
use Php_Parser\Node;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Attribute;
use Php_Parser\Node\Attribute_Group;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Scalar;
use Php_Parser\Node\Scalar\Int_;

class Param_Test extends \PHPUnit\Framework\TestCase
{
    public function createParamBuilder($name)
    {
        return new Param($name);
    }

    /**
     * @dataProvider provideTestDefaultValues
     */
    public function testDefaultValues($value, $expectedValueNode): void
    {
        $node = $this->createParamBuilder('test')
            ->set_default($value)
            ->get_node()
        ;

        $this->assertEquals($expectedValueNode, $node->default);
    }

    public static function provideTestDefaultValues()
    {
        return [
            [
                null,
                new Expr\Const_Fetch(new Node\Name('null')),
            ],
            [
                true,
                new Expr\Const_Fetch(new Node\Name('true')),
            ],
            [
                false,
                new Expr\Const_Fetch(new Node\Name('false')),
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
                    new Node\Array_Item(new Scalar\Int_(1)),
                    new Node\Array_Item(new Scalar\Int_(2)),
                    new Node\Array_Item(new Scalar\Int_(3)),
                ]),
            ],
            [
                ['foo' => 'bar', 'bar' => 'foo'],
                new Expr\Array_([
                    new Node\Array_Item(
                        new Scalar\String_('bar'),
                        new Scalar\String_('foo')
                    ),
                    new Node\Array_Item(
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

    /**
     * @dataProvider provideTestTypes
     * @dataProvider provideTestNullable_Types
     * @dataProvider provideTestUnion_Types
     */
    public function testTypes($typeHint, $expectedType): void
    {
        $node = $this->createParamBuilder('test')
            ->set_type($typeHint)
            ->get_node()
        ;
        $type = $node->type;

        /* Manually implement comparison to avoid __toString stupidity */
        if ($expectedType instanceof Node\Nullable_Type) {
            $this->assertInstanceOf(get_class($expectedType), $type);
            $expectedType = $expectedType->type;
            $type = $type->type;
        }

        $this->assertInstanceOf(get_class($expectedType), $type);
        $this->assertEquals($expectedType, $type);
    }

    public static function provideTestTypes()
    {
        return [
            ['array', new Node\Identifier('array')],
            ['callable', new Node\Identifier('callable')],
            ['bool', new Node\Identifier('bool')],
            ['int', new Node\Identifier('int')],
            ['float', new Node\Identifier('float')],
            ['string', new Node\Identifier('string')],
            ['iterable', new Node\Identifier('iterable')],
            ['object', new Node\Identifier('object')],
            ['Array', new Node\Identifier('array')],
            ['CALLABLE', new Node\Identifier('callable')],
            ['mixed', new Node\Identifier('mixed')],
            ['Some\Class', new Node\Name('Some\Class')],
            ['\Foo', new Node\Name\Fully_Qualified('Foo')],
            ['self', new Node\Name('self')],
            [new Node\Name('Some\Class'), new Node\Name('Some\Class')],
        ];
    }

    public static function provideTestNullable_Types()
    {
        return [
            ['?array', new Node\Nullable_Type(new Node\Identifier('array'))],
            ['?Some\Class', new Node\Nullable_Type(new Node\Name('Some\Class'))],
            [
                new Node\Nullable_Type(new Node\Identifier('int')),
                new Node\Nullable_Type(new Node\Identifier('int')),
            ],
            [
                new Node\Nullable_Type(new Node\Name('Some\Class')),
                new Node\Nullable_Type(new Node\Name('Some\Class')),
            ],
        ];
    }

    public static function provideTestUnion_Types()
    {
        return [
            [
                new Node\Union_Type([
                    new Node\Name('Some\Class'),
                    new Node\Identifier('array'),
                ]),
                new Node\Union_Type([
                    new Node\Name('Some\Class'),
                    new Node\Identifier('array'),
                ]),
            ],
            [
                new Node\Union_Type([
                    new Node\Identifier('self'),
                    new Node\Identifier('array'),
                    new Node\Name\Fully_Qualified('Foo'),
                ]),
                new Node\Union_Type([
                    new Node\Identifier('self'),
                    new Node\Identifier('array'),
                    new Node\Name\Fully_Qualified('Foo'),
                ]),
            ],
        ];
    }

    public function testVoidTypeError(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Parameter type cannot be void');
        $this->createParamBuilder('test')->set_type('void');
    }

    public function testInvalidTypeError(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Type must be a string, or an instance of Name, Identifier or ComplexType');
        $this->createParamBuilder('test')->set_type(new \stdClass());
    }

    public function testByRef(): void
    {
        $node = $this->createParamBuilder('test')
            ->make_by_ref()
            ->get_node()
        ;

        $this->assertEquals(
            new Node\Param(new Expr\Variable('test'), null, null, true),
            $node
        );
    }

    public function testVariadic(): void
    {
        $node = $this->createParamBuilder('test')
            ->make_variadic()
            ->get_node()
        ;

        $this->assertEquals(
            new Node\Param(new Expr\Variable('test'), null, null, false, true),
            $node
        );
    }

    public function testMakePublic(): void
    {
        $node = $this->createParamBuilder('test')
            ->make_public()
            ->get_node()
        ;

        $this->assertEquals(
            new Node\Param(new Expr\Variable('test'), null, null, false, false, [], Modifiers::PUBLIC),
            $node
        );
    }

    public function testMakeProtected(): void
    {
        $node = $this->createParamBuilder('test')
            ->make_protected()
            ->get_node()
        ;

        $this->assertEquals(
            new Node\Param(new Expr\Variable('test'), null, null, false, false, [], Modifiers::PROTECTED),
            $node
        );

        $node = $this->createParamBuilder('test')
            ->make_protected_set()
            ->get_node()
        ;

        $this->assertEquals(
            new Node\Param(new Expr\Variable('test'), null, null, false, false, [], Modifiers::PROTECTED_SET),
            $node
        );
    }

    public function testMakePrivate(): void
    {
        $node = $this->createParamBuilder('test')
            ->make_private()
            ->get_node()
        ;

        $this->assertEquals(
            new Node\Param(new Expr\Variable('test'), null, null, false, false, [], Modifiers::PRIVATE),
            $node
        );

        $node = $this->createParamBuilder('test')
            ->make_private_set()
            ->get_node()
        ;

        $this->assertEquals(
            new Node\Param(new Expr\Variable('test'), null, null, false, false, [], Modifiers::PRIVATE_SET),
            $node
        );
    }

    public function testMakeReadonly(): void
    {
        $node = $this->createParamBuilder('test')
            ->make_readonly()
            ->get_node()
        ;

        $this->assertEquals(
            new Node\Param(new Expr\Variable('test'), null, null, false, false, [], Modifiers::READONLY),
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

        $node = $this->createParamBuilder('attributeGroup')
            ->add_attribute($attributeGroup)
            ->get_node();

        $this->assertEquals(
            new Node\Param(new Expr\Variable('attributeGroup'), null, null, false, false, [], 0, [$attributeGroup]),
            $node
        );
    }
}
