<?php

declare(strict_types=1);

namespace Php_Parser\Node\Stmt;

use Php_Parser\Modifiers;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Name;
use Php_Parser\Node\Param;

class Class_Method_Test extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provideModifiers
     */
    public function testModifiers($modifier): void
    {
        $node = new Class_Method('foo', [
            'type' => constant(Modifiers::class . '::' . strtoupper($modifier)),
        ]);

        $this->assertTrue($node->{'is_' . $modifier}());
    }

    public function testNoModifiers(): void
    {
        $node = new Class_Method('foo', ['type' => 0]);

        $this->assertTrue($node->is_public());
        $this->assertFalse($node->is_protected());
        $this->assertFalse($node->is_private());
        $this->assertFalse($node->is_abstract());
        $this->assertFalse($node->is_final());
        $this->assertFalse($node->is_static());
        $this->assertFalse($node->is_magic());
    }

    public static function provideModifiers()
    {
        return [
            ['public'],
            ['protected'],
            ['private'],
            ['abstract'],
            ['final'],
            ['static'],
        ];
    }

    /**
     * Checks that implicit public modifier detection for method is working
     *
     * @dataProvider implicitPublicModifiers
     *
     * @param string $modifier Node type modifier
     */
    public function testImplicitPublic(string $modifier): void
    {
        $node = new Class_Method('foo', [
            'type' => constant(Modifiers::class . '::' . strtoupper($modifier)),
        ]);

        $this->assertTrue($node->is_public(), 'Node should be implicitly public');
    }

    public static function implicitPublicModifiers()
    {
        return [
            ['abstract'],
            ['final'],
            ['static'],
        ];
    }

    /**
     * @dataProvider provideMagics
     *
     * @param string $name Node name
     */
    public function testMagic(string $name): void
    {
        $node = new Class_Method($name);
        $this->assertTrue($node->is_magic(), 'Method should be magic');
    }

    public static function provideMagics()
    {
        return [
             ['__construct'],
             ['__DESTRUCT'],
             ['__caLL'],
             ['__callstatic'],
             ['__get'],
             ['__set'],
             ['__isset'],
             ['__unset'],
             ['__sleep'],
             ['__wakeup'],
             ['__tostring'],
             ['__set_state'],
             ['__clone'],
             ['__invoke'],
             ['__debuginfo'],
        ];
    }

    public function testFunctionLike(): void
    {
        $param = new Param(new Variable('a'));
        $type = new Name('Foo');
        $return = new Return_(new Variable('a'));
        $method = new Class_Method('test', [
            'byRef' => false,
            'params' => [$param],
            'returnType' => $type,
            'stmts' => [$return],
        ]);

        $this->assertFalse($method->returns_by_ref());
        $this->assertSame([$param], $method->get_params());
        $this->assertSame($type, $method->get_return_type());
        $this->assertSame([$return], $method->get_stmts());

        $method = new Class_Method('test', [
            'byRef' => true,
            'stmts' => null,
        ]);

        $this->assertTrue($method->returns_by_ref());
        $this->assertNull($method->get_stmts());
    }
}
