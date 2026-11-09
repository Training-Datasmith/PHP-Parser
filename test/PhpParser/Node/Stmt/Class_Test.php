<?php

declare(strict_types=1);

namespace Php_Parser\Node\Stmt;

use Php_Parser\Modifiers;
use Php_Parser\Node\Property_Item;
use Php_Parser\Node\Scalar\String_;

class Class_Test extends \PHPUnit\Framework\TestCase
{
    public function testIsAbstract(): void
    {
        $class = new Class_('Foo', ['type' => Modifiers::ABSTRACT]);
        $this->assertTrue($class->is_abstract());

        $class = new Class_('Foo');
        $this->assertFalse($class->is_abstract());
    }

    public function testIsFinal(): void
    {
        $class = new Class_('Foo', ['type' => Modifiers::FINAL]);
        $this->assertTrue($class->is_final());

        $class = new Class_('Foo');
        $this->assertFalse($class->is_final());
    }

    public function testGetTraitUses(): void
    {
        $traitUses = [
            new Trait_Use([new Trait_('foo')]),
            new Trait_Use([new Trait_('bar')]),
        ];
        $class = new Class_('Foo', [
            'stmts' => [
                $traitUses[0],
                new Class_Method('fooBar'),
                $traitUses[1],
            ],
        ]);

        $this->assertSame($traitUses, $class->get_trait_uses());
    }

    public function testGetMethods(): void
    {
        $methods = [
            new Class_Method('foo'),
            new Class_Method('bar'),
            new Class_Method('fooBar'),
        ];
        $class = new Class_('Foo', [
            'stmts' => [
                new Trait_Use([]),
                $methods[0],
                new Class_Const([]),
                $methods[1],
                new Property(0, []),
                $methods[2],
            ],
        ]);

        $this->assertSame($methods, $class->get_methods());
    }

    public function testGetConstants(): void
    {
        $constants = [
            new Class_Const([new \Php_Parser\Node\Const_('foo', new String_('foo_value'))]),
            new Class_Const([new \Php_Parser\Node\Const_('bar', new String_('bar_value'))]),
        ];
        $class = new Class_('Foo', [
            'stmts' => [
                new Trait_Use([]),
                $constants[0],
                new Class_Method('fooBar'),
                $constants[1],
            ],
        ]);

        $this->assertSame($constants, $class->get_constants());
    }

    public function testGetProperties(): void
    {
        $properties = [
            new Property(Modifiers::PUBLIC, [new Property_Item('foo')]),
            new Property(Modifiers::PUBLIC, [new Property_Item('bar')]),
        ];
        $class = new Class_('Foo', [
            'stmts' => [
                new Trait_Use([]),
                $properties[0],
                new Class_Const([]),
                $properties[1],
                new Class_Method('fooBar'),
            ],
        ]);

        $this->assertSame($properties, $class->get_properties());
    }

    public function testGetProperty(): void
    {
        $properties = [
            $fooProp = new Property(Modifiers::PUBLIC, [new Property_Item('foo1')]),
            $barProp = new Property(Modifiers::PUBLIC, [new Property_Item('BAR1')]),
            $fooBarProp = new Property(Modifiers::PUBLIC, [new Property_Item('foo2'), new Property_Item('bar2')]),
        ];
        $class = new Class_('Foo', [
            'stmts' => [
                new Trait_Use([]),
                $properties[0],
                new Class_Const([]),
                $properties[1],
                new Class_Method('fooBar'),
                $properties[2],
            ],
        ]);

        $this->assertSame($fooProp, $class->get_property('foo1'));
        $this->assertSame($barProp, $class->get_property('BAR1'));
        $this->assertSame($fooBarProp, $class->get_property('foo2'));
        $this->assertSame($fooBarProp, $class->get_property('bar2'));
        $this->assertNull($class->get_property('bar1'));
        $this->assertNull($class->get_property('nonExisting'));
    }

    public function testGetMethod(): void
    {
        $methodConstruct = new Class_Method('__CONSTRUCT');
        $methodTest = new Class_Method('test');
        $class = new Class_('Foo', [
            'stmts' => [
                new Class_Const([]),
                $methodConstruct,
                new Property(0, []),
                $methodTest,
            ],
        ]);

        $this->assertSame($methodConstruct, $class->get_method('__construct'));
        $this->assertSame($methodTest, $class->get_method('test'));
        $this->assertNull($class->get_method('nonExisting'));
    }
}
