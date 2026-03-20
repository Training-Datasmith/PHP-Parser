<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node\Stmt;
class Halt_Compiler extends Stmt
{
    /** @var string Remaining text after halt compiler statement. */
    public string $remaining;
    /**
     * Constructs a __halt_compiler node.
     *
     * @param string $remaining Remaining text after halt compiler statement.
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(string $remaining, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->remaining = $remaining;
    }
    public function get_sub_node_names(): array
    {
        return ['remaining'];
    }
    public function get_type(): string
    {
        return 'Stmt_HaltCompiler';
    }
}