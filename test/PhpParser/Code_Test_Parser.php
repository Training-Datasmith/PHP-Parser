<?php

declare(strict_types=1);

namespace Php_Parser;

class Code_Test_Parser
{
    public function parse_test($code, $chunksPerTest)
    {
        $code = canonicalize($code);

        // evaluate @@{expr}@@ expressions
        $code = preg_replace_callback(
            '/@@\{(.*?)\}@@/',
            function ($matches) {
                return eval('return ' . $matches[1] . ';');
            },
            $code
        );

        // parse sections
        $parts = preg_split("/\n-----(?:\n|$)/", $code);

        // first part is the name
        $name = array_shift($parts);

        // multiple sections possible with always two forming a pair
        $chunks = array_chunk($parts, $chunksPerTest);
        $tests = [];
        foreach ($chunks as $chunk) {
            $lastPart = array_pop($chunk);
            list($lastPart, $mode) = $this->extract_mode($lastPart);
            $tests[] = [$mode, array_merge($chunk, [$lastPart])];
        }

        return [$name, $tests];
    }

    public function reconstruct_test($name, array $tests)
    {
        $result = $name;
        foreach ($tests as list($mode, $parts)) {
            $lastPart = array_pop($parts);
            foreach ($parts as $part) {
                $result .= "\n-----\n$part";
            }

            $result .= "\n-----\n";
            if (null !== $mode) {
                $result .= "!!$mode\n";
            }
            $result .= $lastPart;
        }
        return $result . "\n";
    }

    private function extract_mode(string $expected): array
    {
        $firstNewLine = strpos($expected, "\n");
        if (false === $firstNewLine) {
            $firstNewLine = strlen($expected);
        }

        $firstLine = substr($expected, 0, $firstNewLine);
        if (0 !== strpos($firstLine, '!!')) {
            return [$expected, null];
        }

        $expected = substr($expected, $firstNewLine + 1);
        return [$expected, substr($firstLine, 2)];
    }
}
