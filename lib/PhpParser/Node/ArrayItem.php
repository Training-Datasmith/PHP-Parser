<?php

declare (strict_types=1);
namespace Php_Parser\Node;

use Php_Parser\Node_Abstract;
class Array_Item extends Node_Abstract
{
    /** @var null|Expr Key */
    public ?Expr $key;
    /** @var Expr Value */
    public Expr $value;
    /** @var bool Whether to assign by reference */
    public bool $by_ref;
    /** @var bool Whether to unpack the argument */
    public bool $unpack;
    /**
     * Constructs an array item node.
     *
     * @param Expr $value Value
     * @param null|Expr $key Key
     * @param bool $byRef Whether to assign by reference
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Expr $value, ?Expr $key = null, bool $by_ref = false, array $attributes = [], bool $unpack = false)
    {
        $this->attributes = $attributes;
        $this->key = $key;
        $this->value = $value;
        $this->by_ref = $by_ref;
        $this->unpack = $unpack;
    }
    public function get_sub_node_names(): array
    {
        return ['key', 'value', 'byRef', 'unpack'];
    }
    public function get_type(): string
    {
        return 'ArrayItem';
    }
}
// @deprecated compatibility alias
class_alias(Array_Item::class, Expr\Array_Item::class);