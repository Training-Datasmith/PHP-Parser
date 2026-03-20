<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
class Class_Const_Fetch extends Expr
{
    /** @var Name|Expr Class name */
    public Node $class;
    /** @var Identifier|Expr|Error Constant name */
    public Node $name;
    /**
     * Constructs a class const fetch node.
     *
     * @param Name|Expr $class Class name
     * @param string|Identifier|Expr|Error $name Constant name
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Node $class, $name, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->class = $class;
        $this->name = \is_string($name) ? new Identifier($name) : $name;
    }
    public function get_sub_node_names(): array
    {
        return ['class', 'name'];
    }
    public function get_type(): string
    {
        return 'Expr_ClassConstFetch';
    }
}