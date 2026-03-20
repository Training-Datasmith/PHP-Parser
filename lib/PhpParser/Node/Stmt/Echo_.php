<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node;
class Echo_ extends Node\Stmt
{
    /** @var Node\Expr[] Expressions */
    public array $exprs;
    /**
     * Constructs an echo node.
     *
     * @param Node\Expr[] $exprs Expressions
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(array $exprs, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->exprs = $exprs;
    }
    public function get_sub_node_names(): array
    {
        return ['exprs'];
    }
    public function get_type(): string
    {
        return 'Stmt_Echo';
    }
}