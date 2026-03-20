<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node;
class For_ extends Node\Stmt
{
    /** @var Node\Expr[] Init expressions */
    public array $init;
    /** @var Node\Expr[] Loop conditions */
    public array $cond;
    /** @var Node\Expr[] Loop expressions */
    public array $loop;
    /** @var Node\Stmt[] Statements */
    public array $stmts;
    /**
     * Constructs a for loop node.
     *
     * @param array{
     *     init?: Node\Expr[],
     *     cond?: Node\Expr[],
     *     loop?: Node\Expr[],
     *     stmts?: Node\Stmt[],
     * } $subNodes Array of the following optional subnodes:
     *             'init'  => array(): Init expressions
     *             'cond'  => array(): Loop conditions
     *             'loop'  => array(): Loop expressions
     *             'stmts' => array(): Statements
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(array $sub_nodes = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->init = $sub_nodes['init'] ?? [];
        $this->cond = $sub_nodes['cond'] ?? [];
        $this->loop = $sub_nodes['loop'] ?? [];
        $this->stmts = $sub_nodes['stmts'] ?? [];
    }
    public function get_sub_node_names(): array
    {
        return ['init', 'cond', 'loop', 'stmts'];
    }
    public function get_type(): string
    {
        return 'Stmt_For';
    }
}