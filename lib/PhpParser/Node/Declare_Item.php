<?php

declare (strict_types=1);
namespace Php_Parser\Node;

use Php_Parser\Node;
use Php_Parser\Node_Abstract;
class Declare_Item extends Node_Abstract
{
    /** @var Node\Identifier Key */
    public Identifier $key;
    /** @var Node\Expr Value */
    public Expr $value;
    /**
     * Constructs a declare key=>value pair node.
     *
     * @param string|Node\Identifier $key Key
     * @param Node\Expr $value Value
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct($key, Node\Expr $value, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->key = \is_string($key) ? new Node\Identifier($key) : $key;
        $this->value = $value;
    }
    public function get_sub_node_names(): array
    {
        return ['key', 'value'];
    }
    public function get_type(): string
    {
        return 'DeclareItem';
    }
}
// @deprecated compatibility alias
class_alias(Declare_Item::class, Stmt\Declare_Declare::class);