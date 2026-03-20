<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Function_Like;
class Arrow_Function extends Expr implements Function_Like
{
    /** @var bool Whether the closure is static */
    public bool $static;
    /** @var bool Whether to return by reference */
    public bool $by_ref;
    /** @var Node\Param[] */
    public array $params = [];
    /** @var null|Node\Identifier|Node\Name|Node\ComplexType */
    public ?Node $return_type;
    /** @var Expr Expression body */
    public Expr $expr;
    /** @var Node\AttributeGroup[] */
    public array $attr_groups;
    /**
     * @param array{
     *     expr: Expr,
     *     static?: bool,
     *     byRef?: bool,
     *     params?: Node\Param[],
     *     returnType?: null|Node\Identifier|Node\Name|Node\ComplexType,
     *     attrGroups?: Node\AttributeGroup[]
     * } $subNodes Array of the following subnodes:
     *             'expr'                  : Expression body
     *             'static'     => false   : Whether the closure is static
     *             'byRef'      => false   : Whether to return by reference
     *             'params'     => array() : Parameters
     *             'returnType' => null    : Return type
     *             'attrGroups' => array() : PHP attribute groups
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(array $sub_nodes, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->static = $sub_nodes['static'] ?? false;
        $this->by_ref = $sub_nodes['byRef'] ?? false;
        $this->params = $sub_nodes['params'] ?? [];
        $this->return_type = $sub_nodes['returnType'] ?? null;
        $this->expr = $sub_nodes['expr'];
        $this->attr_groups = $sub_nodes['attrGroups'] ?? [];
    }
    public function get_sub_node_names(): array
    {
        return ['attrGroups', 'static', 'byRef', 'params', 'returnType', 'expr'];
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
    /**
     * @return Node\Stmt\Return_[]
     */
    public function get_stmts(): array
    {
        return [new Node\Stmt\Return_($this->expr)];
    }
    public function get_type(): string
    {
        return 'Expr_ArrowFunction';
    }
}