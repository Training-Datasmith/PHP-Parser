<?php

declare(strict_types=1);

/**
 * Example 2: Traversing and transforming an AST with a custom NodeVisitor.
 *
 * This example shows how to:
 *  - Walk every node in the AST with NodeTraverser + NodeVisitor
 *  - Collect information (gather all function names)
 *  - Transform nodes in-place (rename a function)
 *
 * Run from the repo root:
 *   php examples/02_node_visitor.php
 */

require __DIR__ . '/../vendor/autoload.php';

use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser\Php8;
use PhpParser\PhpVersion;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;

// -----------------------------------------------------------------------
// Production code: two visitors
// -----------------------------------------------------------------------

/**
 * Collects the names of all top-level function declarations encountered
 * during traversal.
 */
final class FunctionNameCollector extends NodeVisitorAbstract
{
    /** @var list<string> */
    private array $names = [];

    public function enterNode(Node $node): null
    {
        if ($node instanceof Function_) {
            $this->names[] = $node->name->toString();
        }

        return null; // null = keep the node as-is
    }

    /** @return list<string> */
    public function getCollectedNames(): array
    {
        return $this->names;
    }
}

/**
 * Renames every occurrence of a specific function declaration.
 *
 * Note: this only renames the declaration, not call sites — a more
 * complete rename would also need to update Expr\FuncCall nodes.
 */
final class FunctionRenamer extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $from,
        private readonly string $to,
    ) {}

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof Function_ && $node->name->toString() === $this->from) {
            // Clone to avoid mutating the original node (good practice).
            $renamed = clone $node;
            $renamed->name = new Node\Identifier($this->to);
            return $renamed;
        }

        return null;
    }
}

// -----------------------------------------------------------------------
// Parse some code
// -----------------------------------------------------------------------

$parser = new Php8(PhpVersion::fromComponents(8, 3));

$code = <<<'PHP'
<?php

function add(int $a, int $b): int
{
    return $a + $b;
}

function multiply(int $a, int $b): int
{
    return $a * $b;
}

function oldName(string $s): string
{
    return strtoupper($s);
}
PHP;

$ast = $parser->parse($code) ?? [];

// -----------------------------------------------------------------------
// Pass 1: collect function names (read-only traversal)
// -----------------------------------------------------------------------

$collector  = new FunctionNameCollector();
$traverser1 = new NodeTraverser($collector);
$traverser1->traverse($ast);

echo "=== Collected function names ===\n";
foreach ($collector->getCollectedNames() as $name) {
    echo "  - {$name}\n";
}
echo "\n";

// -----------------------------------------------------------------------
// Pass 2: rename a function (mutating traversal)
// -----------------------------------------------------------------------

$renamer    = new FunctionRenamer('oldName', 'newName');
$traverser2 = new NodeTraverser($renamer);
$modified   = $traverser2->traverse($ast);

$printer = new PrettyPrinter();
echo "=== Code after renaming 'oldName' → 'newName' ===\n";
echo $printer->prettyPrintFile($modified) . "\n";
