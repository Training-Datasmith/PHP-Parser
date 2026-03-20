<?php

declare (strict_types=1);
namespace Php_Parser\Node;

use Php_Parser\Node_Abstract;
class Arg extends Node_Abstract
{
    /** @var Identifier|null Parameter name (for named parameters) */
    public ?Identifier $name;
    /** @var Expr Value to pass */
    public Expr $value;
    /** @var bool Whether to pass by ref */
    public bool $by_ref;
    /** @var bool Whether to unpack the argument */
    public bool $unpack;
    /**
     * Constructs a function call argument node.
     *
     * @param Expr $value Value to pass
     * @param bool $byRef Whether to pass by ref
     * @param bool $unpack Whether to unpack the argument
     * @param array<string, mixed> $attributes Additional attributes
     * @param Identifier|null $name Parameter name (for named parameters)
     */
    public function __construct(Expr $value, bool $by_ref = false, bool $unpack = false, array $attributes = [], ?Identifier $name = null)
    {
        $this->attributes = $attributes;
        $this->name = $name;
        $this->value = $value;
        $this->by_ref = $by_ref;
        $this->unpack = $unpack;
    }
    public function get_sub_node_names(): array
    {
        return ['name', 'value', 'byRef', 'unpack'];
    }
    public function get_type(): string
    {
        return 'Arg';
    }
}