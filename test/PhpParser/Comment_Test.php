<?php

declare(strict_types=1);

namespace Php_Parser;

class Comment_Test extends \PHPUnit\Framework\TestCase
{
    public function testGetters(): void
    {
        $comment = new Comment(
            '/* Some comment */',
            1,
            10,
            2,
            1,
            27,
            2
        );

        $this->assertSame('/* Some comment */', $comment->get_text());
        $this->assertSame('/* Some comment */', (string) $comment);
        $this->assertSame(1, $comment->get_start_line());
        $this->assertSame(10, $comment->get_start_file_pos());
        $this->assertSame(2, $comment->get_start_token_pos());
        $this->assertSame(1, $comment->get_end_line());
        $this->assertSame(27, $comment->get_end_file_pos());
        $this->assertSame(2, $comment->get_end_token_pos());
    }

    /**
     * @dataProvider provideTestReformatting
     */
    public function testReformatting($commentText, $reformattedText): void
    {
        $comment = new Comment($commentText);
        $this->assertSame($reformattedText, $comment->get_reformatted_text());
    }

    public static function provideTestReformatting()
    {
        return [
            ['// Some text', '// Some text'],
            ['/* Some text */', '/* Some text */'],
            [
                "/**\n     * Some text.\n     * Some more text.\n     */",
                "/**\n * Some text.\n * Some more text.\n */",
            ],
            [
                "/**\r\n     * Some text.\r\n     * Some more text.\r\n     */",
                "/**\n * Some text.\n * Some more text.\n */",
            ],
            [
                "/*\n        Some text.\n        Some more text.\n    */",
                "/*\n    Some text.\n    Some more text.\n*/",
            ],
            [
                "/*\r\n        Some text.\r\n        Some more text.\r\n    */",
                "/*\n    Some text.\n    Some more text.\n*/",
            ],
            [
                "/* Some text.\n       More text.\n       Even more text. */",
                "/* Some text.\n   More text.\n   Even more text. */",
            ],
            [
                "/* Some text.\r\n       More text.\r\n       Even more text. */",
                "/* Some text.\n   More text.\n   Even more text. */",
            ],
            [
                "/* Some text.\n       More text.\n         Indented text. */",
                "/* Some text.\n   More text.\n     Indented text. */",
            ],
            // invalid comment -> no reformatting
            [
                "hello\n    world",
                "hello\n    world",
            ],
        ];
    }
}
