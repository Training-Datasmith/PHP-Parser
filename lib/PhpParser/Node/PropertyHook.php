<?php

declare (strict_types=1);
namespace Php_Parser\Node;

use Php_Parser\Modifiers;
use Php_Parser\Node\Expr\Assign;
use Php_Parser\Node\Expr\Property_Fetch;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Stmt\Expression;
use Php_Parser\Node\Stmt\Return_;
use Php_Parser\Node_Abstract;
class Property_Hook extends Node_Abstract implements Function_Like
{
    /** @var AttributeGroup[] PHP attribute groups */
    public array $attr_groups;
    /** @var int Modifiers */
    public int $flags;
    /** @var bool Whether hook returns by reference */
    public bool $by_ref;
    /** @var Identifier Hook name */
    public Identifier $name;
    /** @var Param[] Parameters */
    public array $params;
    /** @var null|Expr|Stmt[] Hook body */
    public $body;
    /**
     * Constructs a property hook node.
     *
     * @param string|Identifier $name Hook name
     * @param null|Expr|Stmt[] $body Hook body
     * @param array{
     *     flags?: int,
     *     byRef?: bool,
     *     params?: Param[],
     *     attrGroups?: AttributeGroup[],
     * } $subNodes Array of the following optional subnodes:
     *             'flags       => 0      : Flags
     *             'byRef'      => false  : Whether hook returns by reference
     *             'params'     => array(): Parameters
     *             'attrGroups' => array(): PHP attribute groups
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct($name, $body, array $sub_nodes = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->name = \is_string($name) ? new Identifier($name) : $name;
        $this->body = $body;
        $this->flags = $sub_nodes['flags'] ?? 0;
        $this->by_ref = $sub_nodes['byRef'] ?? false;
        $this->params = $sub_nodes['params'] ?? [];
        $this->attr_groups = $sub_nodes['attrGroups'] ?? [];
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
        return null;
    }
    /**
     * Whether the property hook is final.
     */
    public function is_final(): bool
    {
        return (bool) ($this->flags & Modifiers::FINAL);
    }
    public function get_stmts(): ?array
    {
        if ($this->body instanceof Expr) {
            $name = $this->name->to_lower_string();
            if ($name === 'get') {
                return [new Return_($this->body)];
            }
            if ($name === 'set') {
                if (!$this->has_attribute('propertyName')) {
                    throw new \LogicException('Can only use getStmts() on a "set" hook if the "propertyName" attribute is set');
                }
                $prop_name = $this->get_attribute('propertyName');
                $prop = new Property_Fetch(new Variable('this'), (string) $prop_name);
                return [new Expression(new Assign($prop, $this->body))];
            }
            throw new \LogicException('Unknown property hook "' . $name . '"');
        }
        return $this->body;
    }
    public function get_attr_groups(): array
    {
        return $this->attr_groups;
    }
    public function get_type(): string
    {
        return 'PropertyHook';
    }
    public function get_sub_node_names(): array
    {
        return ['attrGroups', 'flags', 'byRef', 'name', 'params', 'body'];
    }
}