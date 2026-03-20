<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Variadic_Placeholder;
class Static_Call extends Call_Like
{
    /** @var Node\Name|Expr Class name */
    public Node $class;
    /** @var Identifier|Expr Method name */
    public Node $name;
    /** @var array<Arg|VariadicPlaceholder> Arguments */
    public array $args;
    /**
     * Constructs a static method call node.
     *
     * @param Node\Name|Expr $class Class name
     * @param string|Identifier|Expr $name Method name
     * @param array<Arg|VariadicPlaceholder> $args Arguments
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Node $class, $name, array $args = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->class = $class;
        $this->name = \is_string($name) ? new Identifier($name) : $name;
        $this->args = $args;
    }
    public function get_sub_node_names(): array
    {
        return ['class', 'name', 'args'];
    }
    public function get_type(): string
    {
        return 'Expr_StaticCall';
    }
    public function get_raw_args(): array
    {
        return $this->args;
    }
}