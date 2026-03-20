<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node;
class Foreach_ extends Node\Stmt
{
    /** @var Node\Expr Expression to iterate */
    public Node\Expr $expr;
    /** @var null|Node\Expr Variable to assign key to */
    public ?Node\Expr $key_var;
    /** @var bool Whether to assign value by reference */
    public bool $by_ref;
    /** @var Node\Expr Variable to assign value to */
    public Node\Expr $value_var;
    /** @var Node\Stmt[] Statements */
    public array $stmts;
    /**
     * Constructs a foreach node.
     *
     * @param Node\Expr $expr Expression to iterate
     * @param Node\Expr $valueVar Variable to assign value to
     * @param array{
     *     keyVar?: Node\Expr|null,
     *     byRef?: bool,
     *     stmts?: Node\Stmt[],
     * } $subNodes Array of the following optional subnodes:
     *             'keyVar' => null   : Variable to assign key to
     *             'byRef'  => false  : Whether to assign value by reference
     *             'stmts'  => array(): Statements
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Node\Expr $expr, Node\Expr $value_var, array $sub_nodes = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->expr = $expr;
        $this->key_var = $sub_nodes['keyVar'] ?? null;
        $this->by_ref = $sub_nodes['byRef'] ?? false;
        $this->value_var = $value_var;
        $this->stmts = $sub_nodes['stmts'] ?? [];
    }
    public function get_sub_node_names(): array
    {
        return ['expr', 'keyVar', 'byRef', 'valueVar', 'stmts'];
    }
    public function get_type(): string
    {
        return 'Stmt_Foreach';
    }
}