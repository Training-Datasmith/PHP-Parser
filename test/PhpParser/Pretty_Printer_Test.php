<?php

declare(strict_types=1);

namespace Php_Parser;

use Php_Parser\Node\Expr;
use Php_Parser\Node\Interpolated_String_Part;
use Php_Parser\Node\Name;
use Php_Parser\Node\Scalar\Float_;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Scalar\Interpolated_String;
use Php_Parser\Node\Scalar\String_;
use Php_Parser\Node\Stmt;
use Php_Parser\Pretty_Printer\Standard;

class PrettyPrinterTest extends Code_Test_Abstract
{
    /** @return array{0: Parser, 1: Pretty_Printer} */
    private function createParserAndPrinter(array $options): array
    {
        $parserVersion = $options['parserVersion'] ?? $options['version'] ?? null;
        $printerVersion = $options['version'] ?? null;
        $indent = isset($options['indent']) ? json_decode($options['indent']) : null;
        $factory = new Parser_Factory();
        $parser = $factory->create_for_version($parserVersion !== null
            ? Php_Version::from_string($parserVersion) : Php_Version::get_newest_supported());
        $prettyPrinter = new Standard([
            'phpVersion' => $printerVersion !== null ? Php_Version::from_string($printerVersion) : null,
            'indent' => $indent,
        ]);
        return [$parser, $prettyPrinter];
    }

    protected function doTestPrettyPrintMethod($method, $name, $code, $expected, $modeLine)
    {
        [$parser, $prettyPrinter] = $this->createParserAndPrinter($this->parse_mode_line($modeLine));
        $output = canonicalize($prettyPrinter->$method($parser->parse($code)));
        $this->assertSame($expected, $output, $name);
    }

    /**
     * @dataProvider provideTestPrettyPrint
     */
    public function testPrettyPrint($name, $code, $expected, $mode): void
    {
        $this->doTestPrettyPrintMethod('pretty_print', $name, $code, $expected, $mode);
    }

    /**
     * @dataProvider provideTestPrettyPrintFile
     */
    public function testPrettyPrintFile($name, $code, $expected, $mode): void
    {
        $this->doTestPrettyPrintMethod('pretty_print_file', $name, $code, $expected, $mode);
    }

    public static function provideTestPrettyPrint()
    {
        return self::get_tests(__DIR__ . '/../code/prettyPrinter', 'test');
    }

    public static function provideTestPrettyPrintFile()
    {
        return self::get_tests(__DIR__ . '/../code/prettyPrinter', 'file-test');
    }

    public function testPrettyPrintExpr(): void
    {
        $prettyPrinter = new Standard();
        $expr = new Expr\Binary_Op\Mul(
            new Expr\Binary_Op\Plus(new Expr\Variable('a'), new Expr\Variable('b')),
            new Expr\Variable('c')
        );
        $this->assertEquals('($a + $b) * $c', $prettyPrinter->pretty_print_expr($expr));

        $expr = new Expr\Closure([
            'stmts' => [new Stmt\Return_(new String_("a\nb"))],
        ]);
        $this->assertEquals("function () {\n    return 'a\nb';\n}", $prettyPrinter->pretty_print_expr($expr));
    }

    public function testCommentBeforeInline_Html(): void
    {
        $prettyPrinter = new Pretty_Printer\Standard();
        $comment = new Comment\Doc("/**\n * This is a comment\n */");
        $stmts = [new Stmt\Inline_Html('Hello World!', ['comments' => [$comment]])];
        $expected = "<?php\n\n/**\n * This is a comment\n */\n?>\nHello World!";
        $this->assertSame($expected, $prettyPrinter->pretty_print_file($stmts));
    }

    public function testArraySyntaxDefault(): void
    {
        $prettyPrinter = new Standard(['shortArraySyntax' => true]);
        $expr = new Expr\Array_([
            new Node\Array_Item(new String_('val'), new String_('key')),
        ]);
        $expected = "['key' => 'val']";
        $this->assertSame($expected, $prettyPrinter->pretty_print_expr($expr));
    }

    /**
     * @dataProvider provideTestKindAttributes
     */
    public function testKindAttributes($node, $expected): void
    {
        $prttyPrinter = new Pretty_Printer\Standard();
        $result = $prttyPrinter->pretty_print_expr($node);
        $this->assertSame($expected, $result);
    }

    public static function provideTestKindAttributes()
    {
        $nowdoc = ['kind' => String_::KIND_NOWDOC, 'docLabel' => 'STR'];
        $heredoc = ['kind' => String_::KIND_HEREDOC, 'docLabel' => 'STR'];
        return [
            // Defaults to single quoted
            [new String_('foo'), "'foo'"],
            // Explicit single/double quoted
            [new String_('foo', ['kind' => String_::KIND_SINGLE_QUOTED]), "'foo'"],
            [new String_('foo', ['kind' => String_::KIND_DOUBLE_QUOTED]), '"foo"'],
            // Fallback from doc string if no label
            [new String_('foo', ['kind' => String_::KIND_NOWDOC]), "'foo'"],
            [new String_('foo', ['kind' => String_::KIND_HEREDOC]), '"foo"'],
            // Fallback if string contains label
            [new String_("A\nB\nC", ['kind' => String_::KIND_NOWDOC, 'docLabel' => 'A']), "'A\nB\nC'"],
            [new String_("A\nB\nC", ['kind' => String_::KIND_NOWDOC, 'docLabel' => 'B']), "'A\nB\nC'"],
            [new String_("A\nB\nC", ['kind' => String_::KIND_NOWDOC, 'docLabel' => 'C']), "'A\nB\nC'"],
            [new String_('STR;', $nowdoc), "'STR;'"],
            [new String_('STR,', $nowdoc), "'STR,'"],
            [new String_(' STR', $nowdoc), "' STR'"],
            [new String_("\tSTR", $nowdoc), "'\tSTR'"],
            [new String_("STR\x80", $heredoc), '"STR\x80"'],
            // Doc string if label not contained (or not in ending position)
            [new String_('foo', $nowdoc), "<<<'STR'\nfoo\nSTR"],
            [new String_('foo', $heredoc), "<<<STR\nfoo\nSTR"],
            [new String_('STRx', $nowdoc), "<<<'STR'\nSTRx\nSTR"],
            [new String_('xSTR', $nowdoc), "<<<'STR'\nxSTR\nSTR"],
            [new String_('STRä', $nowdoc), "<<<'STR'\nSTRä\nSTR"],
            [new String_("STR\x80", $nowdoc), "<<<'STR'\nSTR\x80\nSTR"],
            // Empty doc string variations (encapsed variant does not occur naturally)
            [new String_('', $nowdoc), "<<<'STR'\nSTR"],
            [new String_('', $heredoc), "<<<STR\nSTR"],
            [new Interpolated_String([new Interpolated_String_Part('')], $heredoc), "<<<STR\nSTR"],
            // Isolated \r in doc string
            [new String_("\r", $heredoc), "<<<STR\n\\r\nSTR"],
            [new String_("\r", $nowdoc), "'\r'"],
            [new String_("\rx", $nowdoc), "<<<'STR'\n\rx\nSTR"],
            // Encapsed doc string variations
            [new Interpolated_String([new Interpolated_String_Part('foo')], $heredoc), "<<<STR\nfoo\nSTR"],
            [new Interpolated_String([new Interpolated_String_Part('foo'), new Expr\Variable('y')], $heredoc), "<<<STR\nfoo{\$y}\nSTR"],
            [new Interpolated_String([new Expr\Variable('y'), new Interpolated_String_Part("STR\n")], $heredoc), "<<<STR\n{\$y}STR\n\nSTR"],
            // Encapsed doc string fallback
            [new Interpolated_String([new Expr\Variable('y'), new Interpolated_String_Part("\nSTR")], $heredoc), '"{$y}\\nSTR"'],
            [new Interpolated_String([new Interpolated_String_Part("STR\n"), new Expr\Variable('y')], $heredoc), '"STR\\n{$y}"'],
            [new Interpolated_String([new Interpolated_String_Part('STR')], $heredoc), '"STR"'],
            [new Interpolated_String([new Interpolated_String_Part("\nSTR"), new Expr\Variable('y')], $heredoc), '"\nSTR{$y}"'],
            [new Interpolated_String([new Interpolated_String_Part("STR\x80"), new Expr\Variable('y')], $heredoc), '"STR\x80{$y}"'],
        ];
    }

    /** @dataProvider provideTestUnnaturalLiterals */
    public function testUnnaturalLiterals($node, $expected): void
    {
        $prttyPrinter = new Pretty_Printer\Standard();
        $result = $prttyPrinter->pretty_print_expr($node);
        $this->assertSame($expected, $result);
    }

    public static function provideTestUnnaturalLiterals()
    {
        return [
            [new Int_(-1), '-1'],
            [new Int_(-PHP_INT_MAX - 1), '(-' . PHP_INT_MAX . '-1)'],
            [new Int_(-1, ['kind' => Int_::KIND_BIN]), '-0b1'],
            [new Int_(-1, ['kind' => Int_::KIND_OCT]), '-01'],
            [new Int_(-1, ['kind' => Int_::KIND_HEX]), '-0x1'],
            [new Float_(\INF), '1.0E+1000'],
            [new Float_(-\INF), '-1.0E+1000'],
            [new Float_(-\NAN), '\NAN'],
        ];
    }

    /** @dataProvider provideTestCustomRawValue */
    public function printCustomRawValue($node, $expected): void
    {
        $prettyPrinter = new Pretty_Printer\Standard();
        $result = $prettyPrinter->pretty_print_expr($node);
        $this->assertSame($expected, $result);
    }

    public static function provideTestCustomRawValue()
    {
        return [
            // Decimal with separator
            [new Int_(1000, ['rawValue' => '10_00', 'shouldPrintRawValue' => true]), '10_00'],
            // Hexadecimal with separator
            [new Int_(0xDEADBEEF, ['kind' => Int_::KIND_HEX, 'rawValue' => '0xDEAD_BEEF', 'shouldPrintRawValue' => true]), '0xDEAD_BEEF'],
            // Binary with separator
            [new Int_(0b11110000, ['kind' => Int_::KIND_BIN, 'rawValue' => '0b1111_0000', 'shouldPrintRawValue' => true]), '0b1111_0000'],
            // Octal with separator
            [new Int_(0755, ['kind' => Int_::KIND_OCT, 'rawValue' => '0755_000', 'shouldPrintRawValue' => true]), '0755_000'],
            // Without flag set, should use default formatting
            [new Int_(1000, ['rawValue' => '10_00', 'shouldPrintRawValue' => false]), '1000'],
        ];
    }

    public function testPrettyPrintWithError(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot pretty-print AST with Error nodes');
        $stmts = [new Stmt\Expression(
            new Expr\Property_Fetch(new Expr\Variable('a'), new Expr\Error())
        )];
        $prettyPrinter = new Pretty_Printer\Standard();
        $prettyPrinter->pretty_print($stmts);
    }

    public function testPrettyPrintWithErrorInClass_Const_Fetch(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot pretty-print AST with Error nodes');
        $stmts = [new Stmt\Expression(
            new Expr\Class_Const_Fetch(new Name('Foo'), new Expr\Error())
        )];
        $prettyPrinter = new Pretty_Printer\Standard();
        $prettyPrinter->pretty_print($stmts);
    }

    /**
     * @dataProvider provideTestFormatPreservingPrint
     */
    public function testFormatPreservingPrint($name, $code, $modification, $expected, $modeLine): void
    {
        [$parser, $printer] = $this->createParserAndPrinter($this->parse_mode_line($modeLine));
        $traverser = new Node_Traverser(new Node_Visitor\Cloning_Visitor());

        $oldStmts = $parser->parse($code);
        $oldTokens = $parser->get_tokens();

        $newStmts = $traverser->traverse($oldStmts);

        /** @var callable $fn */
        eval(<<<CODE
use Php_Parser\Comment;
use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Scalar;
use Php_Parser\Node\Stmt;
use Php_Parser\Modifiers;
\$fn = function(&\$stmts) { $modification };
CODE
        );
        $fn($newStmts);

        $newCode = $printer->print_format_preserving($newStmts, $oldStmts, $oldTokens);
        $this->assertSame(canonicalize($expected), canonicalize($newCode), $name);
    }

    public static function provideTestFormatPreservingPrint()
    {
        return self::get_tests(__DIR__ . '/../code/formatPreservation', 'test', 3);
    }

    /**
     * @dataProvider provideTestRoundTripPrint
     */
    public function testRoundTripPrint($name, $code, $expected, $modeLine): void
    {
        /**
         * This test makes sure that the format-preserving pretty printer round-trips for all
         * the pretty printer tests (i.e. returns the input if no changes occurred).
         */

        [$parser, $printer] = $this->createParserAndPrinter($this->parse_mode_line($modeLine));
        $traverser = new Node_Traverser(new Node_Visitor\Cloning_Visitor());

        try {
            $oldStmts = $parser->parse($code);
        } catch (Error $e) {
            // Can't do a format-preserving print on a file with errors
            return;
        }

        $oldTokens = $parser->get_tokens();

        $newStmts = $traverser->traverse($oldStmts);

        $newCode = $printer->print_format_preserving($newStmts, $oldStmts, $oldTokens);
        $this->assertSame(canonicalize($code), canonicalize($newCode), $name);
    }

    public static function provideTestRoundTripPrint()
    {
        return array_merge(
            self::get_tests(__DIR__ . '/../code/prettyPrinter', 'test'),
            self::get_tests(__DIR__ . '/../code/parser', 'test')
        );
    }

    public function testWindowsNewline(): void
    {
        $prettyPrinter = new Standard([
            'newline' => "\r\n",
            'phpVersion' => Php_Version::from_components(7, 2),
        ]);
        $stmts = [
            new Stmt\If_(new Int_(1), [
                'stmts' => [
                    new Stmt\Echo_([new String_('Hello')]),
                    new Stmt\Echo_([new String_('World')]),
                ],
            ]),
        ];
        $code = $prettyPrinter->pretty_print($stmts);
        $this->assertSame("if (1) {\r\n    echo 'Hello';\r\n    echo 'World';\r\n}", $code);
        $code = $prettyPrinter->pretty_print_file($stmts);
        $this->assertSame("<?php\r\n\r\nif (1) {\r\n    echo 'Hello';\r\n    echo 'World';\r\n}", $code);

        $stmts = [new Stmt\Inline_Html('Hello world')];
        $code = $prettyPrinter->pretty_print_file($stmts);
        $this->assertSame('Hello world', $code);

        $stmts = [
            new Stmt\Expression(new String_('Test', [
                'kind' => String_::KIND_NOWDOC,
                'docLabel' => 'STR',
            ])),
            new Stmt\Expression(new String_('Test 2', [
                'kind' => String_::KIND_HEREDOC,
                'docLabel' => 'STR',
            ])),
            new Stmt\Expression(new Interpolated_String([new Interpolated_String_Part('Test 3')], [
                'kind' => String_::KIND_HEREDOC,
                'docLabel' => 'STR',
            ])),
        ];
        $code = $prettyPrinter->pretty_print($stmts);
        $this->assertSame(
            "<<<'STR'\r\nTest\r\nSTR;\r\n<<<STR\r\nTest 2\r\nSTR;\r\n<<<STR\r\nTest 3\r\nSTR\r\n;",
            $code
        );
    }

    public function testInvalidNewline(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Option "newline" must be one of "\n" or "\r\n"');
        new Pretty_Printer\Standard(['newline' => 'foo']);
    }

    public function testInvalidIndent(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Option "indent" must either be all spaces or a single tab');
        new Pretty_Printer\Standard(['indent' => "\t  "]);
    }
}
