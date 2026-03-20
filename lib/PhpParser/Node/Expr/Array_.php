<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node\Array_Item;
use Php_Parser\Node\Expr;
class Array_ extends Expr
{
    // For use in "kind" attribute
    public const KIND_LONG = 1;
    // array() syntax
    public const KIND_SHORT = 2;
    // [] syntax
    /** @var ArrayItem[] Items */
    public array $items;
    /**
     * Constructs an array node.
     *
     * @param ArrayItem[] $items Items of the array
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(array $items = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->items = $items;
    }
    public function get_sub_node_names(): array
    {
        return ['items'];
    }
    public function get_type(): string
    {
        return 'Expr_Array';
    }
}