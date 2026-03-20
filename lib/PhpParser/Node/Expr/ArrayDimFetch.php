<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node\Expr;
class Array_Dim_Fetch extends Expr
{
    /** @var Expr Variable */
    public Expr $var;
    /** @var null|Expr Array index / dim */
    public ?Expr $dim;
    /**
     * Constructs an array index fetch node.
     *
     * @param Expr $var Variable
     * @param null|Expr $dim Array index / dim
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Expr $var, ?Expr $dim = null, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->var = $var;
        $this->dim = $dim;
    }
    public function get_sub_node_names(): array
    {
        return ['var', 'dim'];
    }
    public function get_type(): string
    {
        return 'Expr_ArrayDimFetch';
    }
}