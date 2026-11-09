<?php

declare(strict_types=1);

namespace Php_Parser;

class Error_Test extends \PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $attributes = [
            'startLine' => 10,
            'endLine' => 11,
        ];
        $error = new Error('Some error', $attributes);

        $this->assertSame('Some error', $error->get_raw_message());
        $this->assertSame($attributes, $error->get_attributes());
        $this->assertSame(10, $error->get_start_line());
        $this->assertSame(11, $error->get_end_line());
        $this->assertSame('Some error on line 10', $error->getMessage());

        return $error;
    }

    /**
     * @depends testConstruct
     */
    public function testSetMessageAndLine(Error $error): void
    {
        $error->set_raw_message('Some other error');
        $this->assertSame('Some other error', $error->get_raw_message());

        $error->set_start_line(15);
        $this->assertSame(15, $error->get_start_line());
        $this->assertSame('Some other error on line 15', $error->getMessage());
    }

    public function testUnknownLine(): void
    {
        $error = new Error('Some error');

        $this->assertSame(-1, $error->get_start_line());
        $this->assertSame(-1, $error->get_end_line());
        $this->assertSame('Some error on unknown line', $error->getMessage());
    }

    /** @dataProvider provideTestColumnInfo */
    public function testColumnInfo($code, $startPos, $endPos, $startColumn, $endColumn): void
    {
        $error = new Error('Some error', [
            'startFilePos' => $startPos,
            'endFilePos' => $endPos,
        ]);

        $this->assertTrue($error->has_column_info());
        $this->assertSame($startColumn, $error->get_start_column($code));
        $this->assertSame($endColumn, $error->get_end_column($code));
    }

    public static function provideTestColumnInfo()
    {
        return [
            // Error at "bar"
            ['<?php foo bar baz', 10, 12, 11, 13],
            ["<?php\nfoo bar baz", 10, 12, 5, 7],
            ["<?php foo\nbar baz", 10, 12, 1, 3],
            ["<?php foo bar\nbaz", 10, 12, 11, 13],
            ["<?php\r\nfoo bar baz", 11, 13, 5, 7],
            // Error at "baz"
            ['<?php foo bar baz', 14, 16, 15, 17],
            ["<?php foo bar\nbaz", 14, 16, 1, 3],
            // Error at string literal
            ["<?php foo 'bar\nbaz' xyz", 10, 18, 11, 4],
            ["<?php\nfoo 'bar\nbaz' xyz", 10, 18, 5, 4],
            ["<?php foo\n'\nbarbaz\n'\nxyz", 10, 19, 1, 1],
            // Error over full string
            ['<?php', 0, 4, 1, 5],
            ["<?\nphp", 0, 5, 1, 3],
        ];
    }

    public function testNoColumnInfo(): void
    {
        $error = new Error('Some error', ['startLine' => 3]);

        $this->assertFalse($error->has_column_info());
        try {
            $error->get_start_column('');
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame('Error does not have column information', $e->getMessage());
        }
        try {
            $error->get_end_column('');
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame('Error does not have column information', $e->getMessage());
        }
    }

    public function testInvalidPosInfo(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid position information');
        $error = new Error('Some error', [
            'startFilePos' => 10,
            'endFilePos' => 11,
        ]);
        $error->get_start_column('code');
    }
}
