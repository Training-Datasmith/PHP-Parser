<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
require $root . '/test/bootstrap.php';

use function Php_Parser\canonicalize;
use function Php_Parser\filesInDir;

require $root . '/test/PhpParser/Code_Test_Abstract.php';
require $root . '/test/PhpParser/Code_Parsing_Test.php';

use Php_Parser\Code_Parsing_Test;
use Php_Parser\Code_Test_Abstract;
use Php_Parser\Code_Test_Parser;
use Php_Parser\Node_Traverser;
use Php_Parser\Node_Visitor\Cloning_Visitor;
use Php_Parser\Parser;
use Php_Parser\Parser_Factory;
use Php_Parser\Php_Version;
use Php_Parser\Pretty_Printer;

/** @param list<string> $dirs */
function regenerate_parser_dir(string $root, array $dirs): int
{
    $updater = new class () extends Code_Parsing_Test {
        public function regenerate_file(string $path): bool
        {
            $contents = file_get_contents($path);
            $parser = new Code_Test_Parser();
            [$name, $tests] = $parser->parse_test($contents, 2);
            $changed = false;
            foreach ($tests as $i => &$test) {
                [$mode, $parts] = $test;
                [$code, $oldExpected] = $parts;
                $modes = $this->parse_mode_line($mode);
                $versionParser = $this->create_parser($modes['version'] ?? null);
                [, $output] = $this->get_parse_output($versionParser, $code, $modes);
                if ($output !== $oldExpected) {
                    $parts[1] = $output;
                    $test = [$mode, $parts];
                    $changed = true;
                }
            }
            unset($test);
            if ($changed) {
                file_put_contents($path, $parser->reconstruct_test($name, $tests));
            }
            return $changed;
        }
    };

    $count = 0;
    foreach ($dirs as $dir) {
        $full = $root . '/' . $dir;
        foreach (filesInDir($full, 'test') as $fileName => $_) {
            if ($updater->regenerate_file($fileName)) {
                ++$count;
            }
        }
    }
    return $count;
}

/**
 * @return array{0: Parser, 1: Pretty_Printer\Standard}
 */
function create_parser_and_printer(array $options): array
{
    $parserVersion = $options['parserVersion'] ?? $options['version'] ?? null;
    $printerVersion = $options['version'] ?? null;
    $indent = isset($options['indent']) ? json_decode($options['indent']) : null;
    $factory = new Parser_Factory();
    $parser = $factory->create_for_version($parserVersion !== null
        ? Php_Version::from_string($parserVersion) : Php_Version::get_newest_supported());
    $prettyPrinter = new Pretty_Printer\Standard([
        'phpVersion' => $printerVersion !== null ? Php_Version::from_string($printerVersion) : null,
        'indent' => $indent,
    ]);
    return [$parser, $prettyPrinter];
}

/** @param list<string> $dirs */
function regenerate_pretty_printer_dir(string $root, array $dirs): int
{
    $modeParser = new class () extends Code_Test_Abstract {
        public function modes(?string $modeLine): array
        {
            return $this->parse_mode_line($modeLine);
        }
    };

    $regenerateFile = static function (string $path) use ($modeParser): bool {
        $contents = file_get_contents($path);
        $parser = new Code_Test_Parser();
        [$name, $tests] = $parser->parse_test($contents, 3);
        $changed = false;
        foreach ($tests as &$test) {
            [$mode, $parts] = $test;
            [$code, $modification, $oldExpected] = $parts;
            [$p, $printer] = create_parser_and_printer($modeParser->modes($mode));
            $traverser = new Node_Traverser(new Cloning_Visitor());
            $oldStmts = $p->parse($code);
            $oldTokens = $p->get_tokens();
            $newStmts = $traverser->traverse($oldStmts);
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
            $output = canonicalize($newCode);
            if ($output !== $oldExpected) {
                $parts[2] = $output;
                $test = [$mode, $parts];
                $changed = true;
            }
        }
        unset($test);
        if ($changed) {
            file_put_contents($path, $parser->reconstruct_test($name, $tests));
        }
        return $changed;
    };

    $count = 0;
    foreach ($dirs as $dir) {
        $full = $root . '/' . $dir;
        foreach (filesInDir($full, 'test') as $fileName => $_) {
            if ($regenerateFile($fileName)) {
                ++$count;
            }
        }
    }
    return $count;
}

$parserChanged = regenerate_parser_dir($root, ['test/code/parser']);
$ppChanged = regenerate_pretty_printer_dir($root, ['test/code/formatPreservation']);

fwrite(STDOUT, "regenerate-code-test-expectations: parser={$parserChanged}, formatPreservation={$ppChanged}\n");
