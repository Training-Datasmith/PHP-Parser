<?php

declare (strict_types=1);
namespace Php_Parser\Node;

use Php_Parser\Node_Abstract;
class Const_ extends Node_Abstract
{
    /** @var Identifier Name */
    public Identifier $name;
    /** @var Expr Value */
    public Expr $value;
    /** @var Name|null Namespaced name (if using NameResolver) */
    public ?Name $namespaced_name = null;
    /**
     * Constructs a const node for use in class const and const statements.
     *
     * @param string|Identifier $name Name
     * @param Expr $value Value
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct($name, Expr $value, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->name = \is_string($name) ? new Identifier($name) : $name;
        $this->value = $value;
    }
    public function get_sub_node_names(): array
    {
        return ['name', 'value'];
    }
    public function get_type(): string
    {
        return 'Const';
    }
}