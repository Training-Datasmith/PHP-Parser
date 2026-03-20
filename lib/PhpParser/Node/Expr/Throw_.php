<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node;
class Throw_ extends Node\Expr
{
    /** @var Node\Expr Expression */
    public Node\Expr $expr;
    /**
     * Constructs a throw expression node.
     *
     * @param Node\Expr $expr Expression
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Node\Expr $expr, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->expr = $expr;
    }
    public function get_sub_node_names(): array
    {
        return ['expr'];
    }
    public function get_type(): string
    {
        return 'Expr_Throw';
    }
}