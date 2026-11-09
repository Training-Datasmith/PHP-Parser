<?php

declare(strict_types=1);

namespace Php_Parser\Node;

use Php_Parser\Modifiers;
use Php_Parser\Node\Expr\Variable;

class Param_Test extends \PHPUnit\Framework\TestCase
{
    public function testNoModifiers(): void
    {
        $node = new Param(new Variable('foo'));

        $this->assertFalse($node->is_promoted());
        $this->assertFalse($node->is_private());
        $this->assertFalse($node->is_protected());
        $this->assertFalse($node->is_private());
        $this->assertFalse($node->is_readonly());
        $this->assertFalse($node->is_public_set());
        $this->assertFalse($node->is_protected_set());
        $this->assertFalse($node->is_private_set());
    }

    /**
     * @dataProvider provideModifiers
     */
    public function testModifiers(string $modifier): void
    {
        $node = new Param(new Variable('foo'));
        $node->flags = constant(Modifiers::class . '::' . strtoupper($modifier));
        $this->assertTrue($node->is_promoted());
        $this->assertTrue($node->{'is_' . $modifier}());
    }

    public static function provideModifiers()
    {
        return [
            ['public'],
            ['protected'],
            ['private'],
            ['readonly'],
            ['final'],
        ];
    }

    public function testSetVisibility()
    {
        $node = new Param(new Variable('foo'));
        $node->flags = Modifiers::PRIVATE_SET;
        $this->assertTrue($node->is_private_set());
        $this->assertTrue($node->is_public());
        $node->flags = Modifiers::PROTECTED_SET;
        $this->assertTrue($node->is_protected_set());
        $this->assertTrue($node->is_public());
        $node->flags = Modifiers::PUBLIC_SET;
        $this->assertTrue($node->is_public_set());
        $this->assertTrue($node->is_public());
    }

    public function testPromotedPropertyWithoutVisibilityModifier(): void
    {
        $node = new Param(new Variable('foo'));
        $get = new Property_Hook('get', null);
        $node->hooks[] = $get;

        $this->assertTrue($node->is_promoted());
        $this->assertTrue($node->is_public());
    }

    public function testNonPromotedPropertyIsNotPublic(): void
    {
        $node = new Param(new Variable('foo'));
        $this->assertFalse($node->is_public());
    }
}
