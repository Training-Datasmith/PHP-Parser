<?php

declare(strict_types=1);

namespace Php_Parser\Node;

class Identifier_Test extends \PHPUnit\Framework\TestCase
{
    public function testConstructorThrows(): void
    {
        self::expectException(\InvalidArgumentException::class);
        new Identifier('');
    }

    public function testToString(): void
    {
        $identifier = new Identifier('Foo');

        $this->assertSame('Foo', (string) $identifier);
        $this->assertSame('Foo', $identifier->to_string());
        $this->assertSame('foo', $identifier->to_lower_string());
    }

    /** @dataProvider provideTestIsSpecialClassName */
    public function testIsSpecialClassName($identifier, $expected): void
    {
        $identifier = new Identifier($identifier);
        $this->assertSame($expected, $identifier->is_special_class_name());
    }

    public static function provideTestIsSpecialClassName()
    {
        return [
            ['self', true],
            ['PARENT', true],
            ['Static', true],
            ['other', false],
        ];
    }
}
