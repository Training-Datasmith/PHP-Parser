<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Name;
use Php_Parser\Node\Var_Like_Identifier;
class Static_Property_Fetch extends Expr
{
    /** @var Name|Expr Class name */
    public Node $class;
    /** @var VarLikeIdentifier|Expr Property name */
    public Node $name;
    /**
     * Constructs a static property fetch node.
     *
     * @param Name|Expr $class Class name
     * @param string|VarLikeIdentifier|Expr $name Property name
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Node $class, $name, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->class = $class;
        $this->name = \is_string($name) ? new Var_Like_Identifier($name) : $name;
    }
    public function get_sub_node_names(): array
    {
        return ['class', 'name'];
    }
    public function get_type(): string
    {
        return 'Expr_StaticPropertyFetch';
    }
}