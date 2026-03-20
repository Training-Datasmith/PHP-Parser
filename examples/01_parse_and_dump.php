<?php

declare(strict_types=1);

/**
 * Example 1: Parsing PHP code and dumping the resulting AST.
 *
 * This is the most common entry point — parse a string of PHP code into
 * an array of statement nodes, then inspect or print the tree.
 *
 * Run from the repo root:
 *   php examples/01_parse_and_dump.php
 */

require __DIR__ . '/../vendor/autoload.php';

use PhpParser\Error;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\NodeDumper;
use PhpParser\Parser\Php8;
use PhpParser\PhpVersion;

// -----------------------------------------------------------------------
// 1. Create a parser for PHP 8.x syntax
// -----------------------------------------------------------------------

$parser = new Php8(PhpVersion::fromComponents(8, 3));

// -----------------------------------------------------------------------
// 2. Parse a snippet of PHP source code
// -----------------------------------------------------------------------

$code = <<<'PHP'
<?php

function fibonacci(int $n): int
{
    if ($n <= 1) {
        return $n;
    }
    return fibonacci($n - 1) + fibonacci($n - 2);
}

$result = fibonacci(10);
echo "fibonacci(10) = {$result}\n";
PHP;

$ast = $parser->parse($code);

if ($ast === null) {
    echo "Parsing failed.\n";
    exit(1);
}

// -----------------------------------------------------------------------
// 3. Dump the AST as a human-readable string
// -----------------------------------------------------------------------

$dumper = new NodeDumper([
    'dumpComments'  => true,
    'dumpPositions' => true,
]);

echo "=== AST Dump ===\n";
echo $dumper->dump($ast, $code) . "\n\n";

// -----------------------------------------------------------------------
// 4. Parsing with error recovery (CollectingErrorHandler)
//
//    Use this when you want to see a partial AST even if the code has
//    syntax errors — useful in editors and static analysis pipelines.
// -----------------------------------------------------------------------

$errorHandler = new Collecting();

$brokenCode = <<<'PHP'
<?php
function broken( {
    return 42
}
PHP;

$partialAst = $parser->parse($brokenCode, $errorHandler);

echo "=== Errors from broken code ===\n";
foreach ($errorHandler->getErrors() as $error) {
    echo '  Line ' . $error->getStartLine() . ': ' . $error->getRawMessage() . "\n";
}

if ($partialAst !== null) {
    echo "\nPartial AST recovered (" . count($partialAst) . " top-level node(s)).\n";
} else {
    echo "\nNo AST could be recovered.\n";
}
