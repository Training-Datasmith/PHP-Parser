<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node\Expr;
class Variable extends Expr
{
    /** @var string|Expr Name */
    public $name;
    /**
     * Constructs a variable node.
     *
     * @param string|Expr $name Name
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct($name, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->name = $name;
    }
    public function get_sub_node_names(): array
    {
        return ['name'];
    }
    public function get_type(): string
    {
        return 'Expr_Variable';
    }
}