<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Name;
class Instanceof_ extends Expr
{
    /** @var Expr Expression */
    public Expr $expr;
    /** @var Name|Expr Class name */
    public Node $class;
    /**
     * Constructs an instanceof check node.
     *
     * @param Expr $expr Expression
     * @param Name|Expr $class Class name
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Expr $expr, Node $class, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->expr = $expr;
        $this->class = $class;
    }
    public function get_sub_node_names(): array
    {
        return ['expr', 'class'];
    }
    public function get_type(): string
    {
        return 'Expr_Instanceof';
    }
}