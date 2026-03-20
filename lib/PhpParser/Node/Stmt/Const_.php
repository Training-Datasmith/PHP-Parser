<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node;
class Const_ extends Node\Stmt
{
    /** @var Node\Const_[] Constant declarations */
    public array $consts;
    /** @var Node\AttributeGroup[] PHP attribute groups */
    public array $attr_groups;
    /**
     * Constructs a const list node.
     *
     * @param Node\Const_[] $consts Constant declarations
     * @param array<string, mixed> $attributes Additional attributes
     * @param list<Node\AttributeGroup> $attrGroups PHP attribute groups
     */
    public function __construct(array $consts, array $attributes = [], array $attr_groups = [])
    {
        $this->attributes = $attributes;
        $this->attr_groups = $attr_groups;
        $this->consts = $consts;
    }
    public function get_sub_node_names(): array
    {
        return ['attrGroups', 'consts'];
    }
    public function get_type(): string
    {
        return 'Stmt_Const';
    }
}