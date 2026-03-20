<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node\Expr;
use Php_Parser\Node\Name;
class Const_Fetch extends Expr
{
    /** @var Name Constant name */
    public Name $name;
    /**
     * Constructs a const fetch node.
     *
     * @param Name $name Constant name
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Name $name, array $attributes = [])
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
        return 'Expr_ConstFetch';
    }
}