<?php

declare(strict_types=1);

namespace Php_Parser\Node\Stmt;

use Php_Parser\Modifiers;

class Class_Const_Test extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provideModifiers
     */
    public function testModifiers($modifier): void
    {
        $node = new Class_Const(
            [], // invalid
            constant(Modifiers::class . '::' . strtoupper($modifier))
        );

        $this->assertTrue($node->{'is_' . $modifier}());
    }

    public function testNoModifiers(): void
    {
        $node = new Class_Const([], 0);

        $this->assertTrue($node->is_public());
        $this->assertFalse($node->is_protected());
        $this->assertFalse($node->is_private());
        $this->assertFalse($node->is_final());
    }

    public static function provideModifiers()
    {
        return [
            ['public'],
            ['protected'],
            ['private'],
            ['final'],
        ];
    }
}
