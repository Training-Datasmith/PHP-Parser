<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Identifier;
class Nullsafe_Property_Fetch extends Expr
{
    /** @var Expr Variable holding object */
    public Expr $var;
    /** @var Identifier|Expr Property name */
    public Node $name;
    /**
     * Constructs a nullsafe property fetch node.
     *
     * @param Expr $var Variable holding object
     * @param string|Identifier|Expr $name Property name
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Expr $var, $name, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->var = $var;
        $this->name = \is_string($name) ? new Identifier($name) : $name;
    }
    public function get_sub_node_names(): array
    {
        return ['var', 'name'];
    }
    public function get_type(): string
    {
        return 'Expr_NullsafePropertyFetch';
    }
}