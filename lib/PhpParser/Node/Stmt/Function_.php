<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node;
use Php_Parser\Node\Function_Like;
class Function_ extends Node\Stmt implements Function_Like
{
    /** @var bool Whether function returns by reference */
    public bool $by_ref;
    /** @var Node\Identifier Name */
    public Node\Identifier $name;
    /** @var Node\Param[] Parameters */
    public array $params;
    /** @var null|Node\Identifier|Node\Name|Node\ComplexType Return type */
    public ?Node $return_type;
    /** @var Node\Stmt[] Statements */
    public array $stmts;
    /** @var Node\AttributeGroup[] PHP attribute groups */
    public array $attr_groups;
    /** @var Node\Name|null Namespaced name (if using NameResolver) */
    public ?Node\Name $namespaced_name = null;
    /**
     * Constructs a function node.
     *
     * @param string|Node\Identifier $name Name
     * @param array{
     *     byRef?: bool,
     *     params?: Node\Param[],
     *     returnType?: null|Node\Identifier|Node\Name|Node\ComplexType,
     *     stmts?: Node\Stmt[],
     *     attrGroups?: Node\AttributeGroup[],
     * } $subNodes Array of the following optional subnodes:
     *             'byRef'      => false  : Whether to return by reference
     *             'params'     => array(): Parameters
     *             'returnType' => null   : Return type
     *             'stmts'      => array(): Statements
     *             'attrGroups' => array(): PHP attribute groups
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct($name, array $sub_nodes = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->by_ref = $sub_nodes['byRef'] ?? false;
        $this->name = \is_string($name) ? new Node\Identifier($name) : $name;
        $this->params = $sub_nodes['params'] ?? [];
        $this->return_type = $sub_nodes['returnType'] ?? null;
        $this->stmts = $sub_nodes['stmts'] ?? [];
        $this->attr_groups = $sub_nodes['attrGroups'] ?? [];
    }
    public function get_sub_node_names(): array
    {
        return ['attrGroups', 'byRef', 'name', 'params', 'returnType', 'stmts'];
    }
    public function returns_by_ref(): bool
    {
        return $this->by_ref;
    }
    public function get_params(): array
    {
        return $this->params;
    }
    public function get_return_type()
    {
        return $this->return_type;
    }
    public function get_attr_groups(): array
    {
        return $this->attr_groups;
    }
    /** @return Node\Stmt[] */
    public function get_stmts(): array
    {
        return $this->stmts;
    }
    public function get_type(): string
    {
        return 'Stmt_Function';
    }
}