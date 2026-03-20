<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node\Expr;
class Yield_From extends Expr
{
    /** @var Expr Expression to yield from */
    public Expr $expr;
    /**
     * Constructs an "yield from" node.
     *
     * @param Expr $expr Expression
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Expr $expr, array $attributes = [])
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
        return 'Expr_YieldFrom';
    }
}