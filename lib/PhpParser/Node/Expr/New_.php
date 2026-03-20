<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Variadic_Placeholder;
class New_ extends Call_Like
{
    /** @var Node\Name|Expr|Node\Stmt\Class_ Class name */
    public Node $class;
    /** @var array<Arg|VariadicPlaceholder> Arguments */
    public array $args;
    /**
     * Constructs a function call node.
     *
     * @param Node\Name|Expr|Node\Stmt\Class_ $class Class name (or class node for anonymous classes)
     * @param array<Arg|VariadicPlaceholder> $args Arguments
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Node $class, array $args = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->class = $class;
        $this->args = $args;
    }
    public function get_sub_node_names(): array
    {
        return ['class', 'args'];
    }
    public function get_type(): string
    {
        return 'Expr_New';
    }
    public function get_raw_args(): array
    {
        return $this->args;
    }
}