<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node;
class While_ extends Node\Stmt
{
    /** @var Node\Expr Condition */
    public Node\Expr $cond;
    /** @var Node\Stmt[] Statements */
    public array $stmts;
    /**
     * Constructs a while node.
     *
     * @param Node\Expr $cond Condition
     * @param Node\Stmt[] $stmts Statements
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Node\Expr $cond, array $stmts = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->cond = $cond;
        $this->stmts = $stmts;
    }
    public function get_sub_node_names(): array
    {
        return ['cond', 'stmts'];
    }
    public function get_type(): string
    {
        return 'Stmt_While';
    }
}