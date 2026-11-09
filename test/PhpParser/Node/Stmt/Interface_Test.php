<?php

declare(strict_types=1);

namespace Php_Parser\Node\Stmt;

use Php_Parser\Node;
use Php_Parser\Node\Scalar\String_;

class Interface_Test extends \PHPUnit\Framework\TestCase
{
    public function testGetMethods(): void
    {
        $methods = [
            new Class_Method('foo'),
            new Class_Method('bar'),
        ];
        $interface = new Interface_('Foo', [
            'stmts' => [
                new Node\Stmt\Class_Const([new Node\Const_('C1', new Node\Scalar\String_('C1'))]),
                $methods[0],
                new Node\Stmt\Class_Const([new Node\Const_('C2', new Node\Scalar\String_('C2'))]),
                $methods[1],
                new Node\Stmt\Class_Const([new Node\Const_('C3', new Node\Scalar\String_('C3'))]),
            ],
        ]);

        $this->assertSame($methods, $interface->get_methods());
    }

    public function testGetConst_ants(): void
    {
        $constants = [
            new Class_Const([new \Php_Parser\Node\Const_('foo', new String_('foo_value'))]),
            new Class_Const([new \Php_Parser\Node\Const_('bar', new String_('bar_value'))]),
        ];
        $class = new Interface_('Foo', [
            'stmts' => [
                new Trait_Use([]),
                $constants[0],
                new Class_Method('fooBar'),
                $constants[1],
            ],
        ]);

        $this->assertSame($constants, $class->get_constants());
    }
}
