<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node;
use Php_Parser\Node\Closure_Use;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Function_Like;
class Closure extends Expr implements Function_Like
{
    /** @var bool Whether the closure is static */
    public bool $static;
    /** @var bool Whether to return by reference */
    public bool $by_ref;
    /** @var Node\Param[] Parameters */
    public array $params;
    /** @var ClosureUse[] use()s */
    public array $uses;
    /** @var null|Node\Identifier|Node\Name|Node\ComplexType Return type */
    public ?Node $return_type;
    /** @var Node\Stmt[] Statements */
    public array $stmts;
    /** @var Node\AttributeGroup[] PHP attribute groups */
    public array $attr_groups;
    /**
     * Constructs a lambda function node.
     *
     * @param array{
     *     static?: bool,
     *     byRef?: bool,
     *     params?: Node\Param[],
     *     uses?: ClosureUse[],
     *     returnType?: null|Node\Identifier|Node\Name|Node\ComplexType,
     *     stmts?: Node\Stmt[],
     *     attrGroups?: Node\AttributeGroup[],
     * } $subNodes Array of the following optional subnodes:
     *             'static'     => false  : Whether the closure is static
     *             'byRef'      => false  : Whether to return by reference
     *             'params'     => array(): Parameters
     *             'uses'       => array(): use()s
     *             'returnType' => null   : Return type
     *             'stmts'      => array(): Statements
     *             'attrGroups' => array(): PHP attributes groups
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(array $sub_nodes = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->static = $sub_nodes['static'] ?? false;
        $this->by_ref = $sub_nodes['byRef'] ?? false;
        $this->params = $sub_nodes['params'] ?? [];
        $this->uses = $sub_nodes['uses'] ?? [];
        $this->return_type = $sub_nodes['returnType'] ?? null;
        $this->stmts = $sub_nodes['stmts'] ?? [];
        $this->attr_groups = $sub_nodes['attrGroups'] ?? [];
    }
    public function get_sub_node_names(): array
    {
        return ['attrGroups', 'static', 'byRef', 'params', 'uses', 'returnType', 'stmts'];
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
    /** @return Node\Stmt[] */
    public function get_stmts(): array
    {
        return $this->stmts;
    }
    public function get_attr_groups(): array
    {
        return $this->attr_groups;
    }
    public function get_type(): string
    {
        return 'Expr_Closure';
    }
}