<?php

declare(strict_types=1);

namespace Php_Parser\Node;

class Name_Test extends \PHPUnit\Framework\TestCase
{
    public function testConstruct(): void
    {
        $name = new Name(['foo', 'bar']);
        $this->assertSame('foo\bar', $name->name);

        $name = new Name('foo\bar');
        $this->assertSame('foo\bar', $name->name);

        $name = new Name($name);
        $this->assertSame('foo\bar', $name->name);
    }

    public function testGet(): void
    {
        $name = new Name('foo');
        $this->assertSame('foo', $name->get_first());
        $this->assertSame('foo', $name->get_last());
        $this->assertSame(['foo'], $name->get_parts());

        $name = new Name('foo\bar');
        $this->assertSame('foo', $name->get_first());
        $this->assertSame('bar', $name->get_last());
        $this->assertSame(['foo', 'bar'], $name->get_parts());
    }

    public function testToString(): void
    {
        $name = new Name('Foo\Bar');

        $this->assertSame('Foo\Bar', (string) $name);
        $this->assertSame('Foo\Bar', $name->to_string());
        $this->assertSame('foo\bar', $name->to_lower_string());
    }

    public function testSlice(): void
    {
        $name = new Name('foo\bar\baz');
        $this->assertEquals(new Name('foo\bar\baz'), $name->slice(0));
        $this->assertEquals(new Name('bar\baz'), $name->slice(1));
        $this->assertNull($name->slice(3));
        $this->assertEquals(new Name('foo\bar\baz'), $name->slice(-3));
        $this->assertEquals(new Name('bar\baz'), $name->slice(-2));
        $this->assertEquals(new Name('foo\bar'), $name->slice(0, -1));
        $this->assertNull($name->slice(0, -3));
        $this->assertEquals(new Name('bar'), $name->slice(1, -1));
        $this->assertNull($name->slice(1, -2));
        $this->assertEquals(new Name('bar'), $name->slice(-2, 1));
        $this->assertEquals(new Name('bar'), $name->slice(-2, -1));
        $this->assertNull($name->slice(-2, -2));
    }

    public function testSliceOffsetTooLarge(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Offset 4 is out of bounds');
        (new Name('foo\bar\baz'))->slice(4);
    }

    public function testSliceOffsetTooSmall(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Offset -4 is out of bounds');
        (new Name('foo\bar\baz'))->slice(-4);
    }

    public function testSliceLengthTooLarge(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Length 4 is out of bounds');
        (new Name('foo\bar\baz'))->slice(0, 4);
    }

    public function testSliceLengthTooSmall(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Length -4 is out of bounds');
        (new Name('foo\bar\baz'))->slice(0, -4);
    }

    public function testSliceLengthTooLargeWithOffset(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Length 3 is out of bounds');
        (new Name('foo\bar\baz'))->slice(1, 3);
    }

    public function testConcat(): void
    {
        $this->assertEquals(new Name('foo\bar\baz'), Name::concat('foo', 'bar\baz'));
        $this->assertEquals(
            new Name\Fully_Qualified('foo\bar'),
            Name\Fully_Qualified::concat(['foo'], new Name('bar'))
        );

        $attributes = ['foo' => 'bar'];
        $this->assertEquals(
            new Name\Relative('foo\bar\baz', $attributes),
            Name\Relative::concat(new Name\Fully_Qualified('foo\bar'), 'baz', $attributes)
        );

        $this->assertEquals(new Name('foo'), Name::concat(null, 'foo'));
        $this->assertEquals(new Name('foo'), Name::concat('foo', null));
        $this->assertNull(Name::concat(null, null));
    }

    public function testNameTypes(): void
    {
        $name = new Name('foo');
        $this->assertTrue($name->is_unqualified());
        $this->assertFalse($name->is_qualified());
        $this->assertFalse($name->is_fully_qualified());
        $this->assertFalse($name->is_relative());
        $this->assertSame('foo', $name->to_code_string());

        $name = new Name('foo\bar');
        $this->assertFalse($name->is_unqualified());
        $this->assertTrue($name->is_qualified());
        $this->assertFalse($name->is_fully_qualified());
        $this->assertFalse($name->is_relative());
        $this->assertSame('foo\bar', $name->to_code_string());

        $name = new Name\Fully_Qualified('foo');
        $this->assertFalse($name->is_unqualified());
        $this->assertFalse($name->is_qualified());
        $this->assertTrue($name->is_fully_qualified());
        $this->assertFalse($name->is_relative());
        $this->assertSame('\foo', $name->to_code_string());

        $name = new Name\Relative('foo');
        $this->assertFalse($name->is_unqualified());
        $this->assertFalse($name->is_qualified());
        $this->assertFalse($name->is_fully_qualified());
        $this->assertTrue($name->is_relative());
        $this->assertSame('namespace\foo', $name->to_code_string());
    }

    public function testInvalidArg(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected string, array of parts or Name instance');
        Name::concat('foo', new \stdClass());
    }

    public function testInvalidEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name cannot be empty');
        new Name('');
    }

    public function testInvalidEmptyArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name cannot be empty');
        new Name([]);
    }

    /** @dataProvider provideTestIsSpecialClassName */
    public function testIsSpecialClassName($name, $expected): void
    {
        $name = new Name($name);
        $this->assertSame($expected, $name->is_special_class_name());
    }

    public static function provideTestIsSpecialClassName()
    {
        return [
            ['self', true],
            ['PARENT', true],
            ['Static', true],
            ['self\not', false],
            ['not\self', false],
        ];
    }
}
