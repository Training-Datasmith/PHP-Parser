<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node\Expr;
class Ternary extends Expr
{
    /** @var Expr Condition */
    public Expr $cond;
    /** @var null|Expr Expression for true */
    public ?Expr $if;
    /** @var Expr Expression for false */
    public Expr $else;
    /**
     * Constructs a ternary operator node.
     *
     * @param Expr $cond Condition
     * @param null|Expr $if Expression for true
     * @param Expr $else Expression for false
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Expr $cond, ?Expr $if, Expr $else, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->cond = $cond;
        $this->if = $if;
        $this->else = $else;
    }
    public function get_sub_node_names(): array
    {
        return ['cond', 'if', 'else'];
    }
    public function get_type(): string
    {
        return 'Expr_Ternary';
    }
}