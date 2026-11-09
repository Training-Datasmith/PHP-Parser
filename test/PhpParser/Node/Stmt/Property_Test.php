<?php

declare(strict_types=1);

namespace Php_Parser\Node\Stmt;

use Php_Parser\Modifiers;

class Property_Test extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provideModifiers
     */
    public function testModifiers($modifier): void
    {
        $node = new Property(
            constant(Modifiers::class . '::' . strtoupper($modifier)),
            [] // invalid
        );

        $this->assertTrue($node->{'is_' . $modifier}());
    }

    public function testNoModifiers(): void
    {
        $node = new Property(0, []);

        $this->assertTrue($node->is_public());
        $this->assertFalse($node->is_protected());
        $this->assertFalse($node->is_private());
        $this->assertFalse($node->is_static());
        $this->assertFalse($node->is_readonly());
        $this->assertFalse($node->is_public_set());
        $this->assertFalse($node->is_protected_set());
        $this->assertFalse($node->is_private_set());
    }

    public function testStaticImplicitlyPublic(): void
    {
        $node = new Property(Modifiers::STATIC, []);
        $this->assertTrue($node->is_public());
        $this->assertFalse($node->is_protected());
        $this->assertFalse($node->is_private());
        $this->assertTrue($node->is_static());
        $this->assertFalse($node->is_readonly());
    }

    public static function provideModifiers()
    {
        return [
            ['public'],
            ['protected'],
            ['private'],
            ['static'],
            ['readonly'],
        ];
    }

    public function testSetVisibility()
    {
        $node = new Property(Modifiers::PRIVATE_SET, []);
        $this->assertTrue($node->is_private_set());
        $node = new Property(Modifiers::PROTECTED_SET, []);
        $this->assertTrue($node->is_protected_set());
        $node = new Property(Modifiers::PUBLIC_SET, []);
        $this->assertTrue($node->is_public_set());
    }

    public function testIsFinal()
    {
        $node = new Property(Modifiers::FINAL, []);
        $this->assertTrue($node->is_final());
    }

    public function testIsAbstract()
    {
        $node = new Property(Modifiers::ABSTRACT, []);
        $this->assertTrue($node->is_abstract());
    }
}
