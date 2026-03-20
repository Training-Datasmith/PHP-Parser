<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node\Expr;
class Post_Inc extends Expr
{
    /** @var Expr Variable */
    public Expr $var;
    /**
     * Constructs a post increment node.
     *
     * @param Expr $var Variable
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Expr $var, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->var = $var;
    }
    public function get_sub_node_names(): array
    {
        return ['var'];
    }
    public function get_type(): string
    {
        return 'Expr_PostInc';
    }
}