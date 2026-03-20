<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Modifiers;
use Php_Parser\Node;
use Php_Parser\Node\Function_Like;
class Class_Method extends Node\Stmt implements Function_Like
{
    /** @var int Flags */
    public int $flags;
    /** @var bool Whether to return by reference */
    public bool $by_ref;
    /** @var Node\Identifier Name */
    public Node\Identifier $name;
    /** @var Node\Param[] Parameters */
    public array $params;
    /** @var null|Node\Identifier|Node\Name|Node\ComplexType Return type */
    public ?Node $return_type;
    /** @var Node\Stmt[]|null Statements */
    public ?array $stmts;
    /** @var Node\AttributeGroup[] PHP attribute groups */
    public array $attr_groups;
    /** @var array<string, bool> */
    private static array $magic_names = ['__construct' => true, '__destruct' => true, '__call' => true, '__callstatic' => true, '__get' => true, '__set' => true, '__isset' => true, '__unset' => true, '__sleep' => true, '__wakeup' => true, '__tostring' => true, '__set_state' => true, '__clone' => true, '__invoke' => true, '__debuginfo' => true, '__serialize' => true, '__unserialize' => true];
    /**
     * Constructs a class method node.
     *
     * @param string|Node\Identifier $name Name
     * @param array{
     *     flags?: int,
     *     byRef?: bool,
     *     params?: Node\Param[],
     *     returnType?: null|Node\Identifier|Node\Name|Node\ComplexType,
     *     stmts?: Node\Stmt[]|null,
     *     attrGroups?: Node\AttributeGroup[],
     * } $subNodes Array of the following optional subnodes:
     *             'flags       => 0              : Flags
     *             'byRef'      => false          : Whether to return by reference
     *             'params'     => array()        : Parameters
     *             'returnType' => null           : Return type
     *             'stmts'      => array()        : Statements
     *             'attrGroups' => array()        : PHP attribute groups
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct($name, array $sub_nodes = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->flags = $sub_nodes['flags'] ?? $sub_nodes['type'] ?? 0;
        $this->by_ref = $sub_nodes['byRef'] ?? false;
        $this->name = \is_string($name) ? new Node\Identifier($name) : $name;
        $this->params = $sub_nodes['params'] ?? [];
        $this->return_type = $sub_nodes['returnType'] ?? null;
        $this->stmts = array_key_exists('stmts', $sub_nodes) ? $sub_nodes['stmts'] : [];
        $this->attr_groups = $sub_nodes['attrGroups'] ?? [];
    }
    public function get_sub_node_names(): array
    {
        return ['attrGroups', 'flags', 'byRef', 'name', 'params', 'returnType', 'stmts'];
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
    public function get_stmts(): ?array
    {
        return $this->stmts;
    }
    public function get_attr_groups(): array
    {
        return $this->attr_groups;
    }
    /**
     * Whether the method is explicitly or implicitly public.
     */
    public function is_public(): bool
    {
        return ($this->flags & Modifiers::PUBLIC) !== 0 || ($this->flags & Modifiers::VISIBILITY_MASK) === 0;
    }
    /**
     * Whether the method is protected.
     */
    public function is_protected(): bool
    {
        return (bool) ($this->flags & Modifiers::PROTECTED);
    }
    /**
     * Whether the method is private.
     */
    public function is_private(): bool
    {
        return (bool) ($this->flags & Modifiers::PRIVATE);
    }
    /**
     * Whether the method is abstract.
     */
    public function is_abstract(): bool
    {
        return (bool) ($this->flags & Modifiers::ABSTRACT);
    }
    /**
     * Whether the method is final.
     */
    public function is_final(): bool
    {
        return (bool) ($this->flags & Modifiers::FINAL);
    }
    /**
     * Whether the method is static.
     */
    public function is_static(): bool
    {
        return (bool) ($this->flags & Modifiers::STATIC);
    }
    /**
     * Whether the method is magic.
     */
    public function is_magic(): bool
    {
        return isset(self::$magic_names[$this->name->to_lower_string()]);
    }
    public function get_type(): string
    {
        return 'Stmt_ClassMethod';
    }
}