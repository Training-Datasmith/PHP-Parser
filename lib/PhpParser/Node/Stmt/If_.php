<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node;
class If_ extends Node\Stmt
{
    /** @var Node\Expr Condition expression */
    public Node\Expr $cond;
    /** @var Node\Stmt[] Statements */
    public array $stmts;
    /** @var ElseIf_[] Elseif clauses */
    public array $elseifs;
    /** @var null|Else_ Else clause */
    public ?Else_ $else;
    /**
     * Constructs an if node.
     *
     * @param Node\Expr $cond Condition
     * @param array{
     *     stmts?: Node\Stmt[],
     *     elseifs?: ElseIf_[],
     *     else?: Else_|null,
     * } $subNodes Array of the following optional subnodes:
     *             'stmts'   => array(): Statements
     *             'elseifs' => array(): Elseif clauses
     *             'else'    => null   : Else clause
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Node\Expr $cond, array $sub_nodes = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->cond = $cond;
        $this->stmts = $sub_nodes['stmts'] ?? [];
        $this->elseifs = $sub_nodes['elseifs'] ?? [];
        $this->else = $sub_nodes['else'] ?? null;
    }
    public function get_sub_node_names(): array
    {
        return ['cond', 'stmts', 'elseifs', 'else'];
    }
    public function get_type(): string
    {
        return 'Stmt_If';
    }
}