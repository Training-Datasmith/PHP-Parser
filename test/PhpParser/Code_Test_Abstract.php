<?php

declare(strict_types=1);

namespace Php_Parser;

abstract class Code_Test_Abstract extends \PHPUnit\Framework\TestCase
{
    protected static function get_tests($directory, $fileExtension, $chunksPerTest = 2)
    {
        $parser = new Code_Test_Parser();
        $allTests = [];
        foreach (filesInDir($directory, $fileExtension) as $fileName => $fileContents) {
            list($name, $tests) = $parser->parse_test($fileContents, $chunksPerTest);

            // first part is the name
            $name .= ' (' . $fileName . ')';
            $shortName = ltrim(str_replace($directory, '', $fileName), '/\\');

            // multiple sections possible with always two forming a pair
            foreach ($tests as $i => list($mode, $parts)) {
                $dataSetName = $shortName . (count($parts) > 1 ? '#' . $i : '');
                $allTests[$dataSetName] = array_merge([$name], $parts, [$mode]);
            }
        }

        return $allTests;
    }

    public function parse_mode_line(?string $modeLine): array
    {
        if ($modeLine === null) {
            return [];
        }

        $modes = [];
        foreach (explode(',', $modeLine) as $mode) {
            $kv = explode('=', $mode, 2);
            if (isset($kv[1])) {
                $modes[$kv[0]] = $kv[1];
            } else {
                $modes[$kv[0]] = true;
            }
        }
        return $modes;
    }
}
