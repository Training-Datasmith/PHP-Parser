<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node\Expr;
class Assign_Ref extends Expr
{
    /** @var Expr Variable reference is assigned to */
    public Expr $var;
    /** @var Expr Variable which is referenced */
    public Expr $expr;
    /**
     * Constructs an assignment node.
     *
     * @param Expr $var Variable
     * @param Expr $expr Expression
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Expr $var, Expr $expr, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->var = $var;
        $this->expr = $expr;
    }
    public function get_sub_node_names(): array
    {
        return ['var', 'expr'];
    }
    public function get_type(): string
    {
        return 'Expr_AssignRef';
    }
}