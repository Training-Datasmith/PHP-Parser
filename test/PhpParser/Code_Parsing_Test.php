<?php

declare(strict_types=1);

namespace Php_Parser;

use Php_Parser\Node\Expr;
use Php_Parser\Node\Stmt;

class Code_Parsing_Test extends Code_Test_Abstract
{
    /**
     * @dataProvider provideTestParse
     */
    public function testParse($name, $code, $expected, $modeLine): void
    {
        $modes = $this->parse_mode_line($modeLine);
        $parser = $this->create_parser($modes['version'] ?? null);
        list($stmts, $output) = $this->get_parse_output($parser, $code, $modes);

        $this->assertSame($expected, $output, $name);
        $this->check_attributes($stmts);
    }

    public function create_parser(?string $version): Parser
    {
        $factory = new Parser_Factory();
        $version = $version === null
            ? Php_Version::get_newest_supported() : Php_Version::from_string($version);
        return $factory->create_for_version($version);
    }

    // Must be public for updateTests.php
    public function get_parse_output(Parser $parser, $code, array $modes)
    {
        $dumpPositions = isset($modes['positions']);
        $dumpOtherAttributes = isset($modes['attributes']);

        $errors = new Error_Handler\Collecting();
        $stmts = $parser->parse($code, $errors);

        $output = '';
        foreach ($errors->get_errors() as $error) {
            $output .= $this->format_error_message($error, $code) . "\n";
        }

        if (null !== $stmts) {
            $dumper = new Node_Dumper([
                'dumpComments' => true,
                'dumpPositions' => $dumpPositions,
                'dumpOtherAttributes' => $dumpOtherAttributes,
            ]);
            $output .= $dumper->dump($stmts, $code);
        }

        return [$stmts, canonicalize($output)];
    }

    public static function provideTestParse()
    {
        return self::get_tests(__DIR__ . '/../code/parser', 'test');
    }

    private function format_error_message(Error $e, $code)
    {
        if ($e->has_column_info()) {
            return $e->get_message_with_column_info($code);
        }

        return $e->getMessage();
    }

    private function check_attributes($stmts): void
    {
        if ($stmts === null) {
            return;
        }

        $traverser = new Node_Traverser(new class () extends Node_Visitor_Abstract {
            public function enter_node(Node $node): void
            {
                $startLine = $node->get_start_line();
                $endLine = $node->get_end_line();
                $startFilePos = $node->get_start_file_pos();
                $endFilePos = $node->get_end_file_pos();
                $startTokenPos = $node->get_start_token_pos();
                $endTokenPos = $node->get_end_token_pos();
                if ($startLine < 0 || $endLine < 0 ||
                    $startFilePos < 0 || $endFilePos < 0 ||
                    $startTokenPos < 0 || $endTokenPos < 0
                ) {
                    throw new \Exception('Missing location information on ' . $node->get_type());
                }

                if ($endLine < $startLine ||
                    $endFilePos < $startFilePos ||
                    $endTokenPos < $startTokenPos
                ) {
                    // Nop and Error can have inverted order, if they are empty.
                    // This can also happen for a Param containing an Error.
                    if (!$node instanceof Stmt\Nop && !$node instanceof Expr\Error &&
                        !$node instanceof Node\Param
                    ) {
                        throw new \Exception('End < start on ' . $node->get_type());
                    }
                }
            }
        });
        $traverser->traverse($stmts);
    }
}
