<?php

declare (strict_types=1);
namespace Php_Parser\Node;

use Php_Parser\Node;
use Php_Parser\Node_Abstract;
class Attribute extends Node_Abstract
{
    /** @var Name Attribute name */
    public Name $name;
    /** @var list<Arg> Attribute arguments */
    public array $args;
    /**
     * @param Node\Name $name Attribute name
     * @param list<Arg> $args Attribute arguments
     * @param array<string, mixed> $attributes Additional node attributes
     */
    public function __construct(Name $name, array $args = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->name = $name;
        $this->args = $args;
    }
    public function get_sub_node_names(): array
    {
        return ['name', 'args'];
    }
    public function get_type(): string
    {
        return 'Attribute';
    }
}