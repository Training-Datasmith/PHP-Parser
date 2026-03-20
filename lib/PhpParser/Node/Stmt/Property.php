<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Modifiers;
use Php_Parser\Node;
use Php_Parser\Node\Complex_Type;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Property_Item;
class Property extends Node\Stmt
{
    /** @var int Modifiers */
    public int $flags;
    /** @var PropertyItem[] Properties */
    public array $props;
    /** @var null|Identifier|Name|ComplexType Type declaration */
    public ?Node $type;
    /** @var Node\AttributeGroup[] PHP attribute groups */
    public array $attr_groups;
    /** @var Node\PropertyHook[] Property hooks */
    public array $hooks;
    /**
     * Constructs a class property list node.
     *
     * @param int $flags Modifiers
     * @param PropertyItem[] $props Properties
     * @param array<string, mixed> $attributes Additional attributes
     * @param null|Identifier|Name|ComplexType $type Type declaration
     * @param Node\AttributeGroup[] $attrGroups PHP attribute groups
     * @param Node\PropertyHook[] $hooks Property hooks
     */
    public function __construct(int $flags, array $props, array $attributes = [], ?Node $type = null, array $attr_groups = [], array $hooks = [])
    {
        $this->attributes = $attributes;
        $this->flags = $flags;
        $this->props = $props;
        $this->type = $type;
        $this->attr_groups = $attr_groups;
        $this->hooks = $hooks;
    }
    public function get_sub_node_names(): array
    {
        return ['attrGroups', 'flags', 'type', 'props', 'hooks'];
    }
    /**
     * Whether the property is explicitly or implicitly public.
     */
    public function is_public(): bool
    {
        return ($this->flags & Modifiers::PUBLIC) !== 0 || ($this->flags & Modifiers::VISIBILITY_MASK) === 0;
    }
    /**
     * Whether the property is protected.
     */
    public function is_protected(): bool
    {
        return (bool) ($this->flags & Modifiers::PROTECTED);
    }
    /**
     * Whether the property is private.
     */
    public function is_private(): bool
    {
        return (bool) ($this->flags & Modifiers::PRIVATE);
    }
    /**
     * Whether the property is static.
     */
    public function is_static(): bool
    {
        return (bool) ($this->flags & Modifiers::STATIC);
    }
    /**
     * Whether the property is readonly.
     */
    public function is_readonly(): bool
    {
        return (bool) ($this->flags & Modifiers::READONLY);
    }
    /**
     * Whether the property is abstract.
     */
    public function is_abstract(): bool
    {
        return (bool) ($this->flags & Modifiers::ABSTRACT);
    }
    /**
     * Whether the property is final.
     */
    public function is_final(): bool
    {
        return (bool) ($this->flags & Modifiers::FINAL);
    }
    /**
     * Whether the property has explicit public(set) visibility.
     */
    public function is_public_set(): bool
    {
        return (bool) ($this->flags & Modifiers::PUBLIC_SET);
    }
    /**
     * Whether the property has explicit protected(set) visibility.
     */
    public function is_protected_set(): bool
    {
        return (bool) ($this->flags & Modifiers::PROTECTED_SET);
    }
    /**
     * Whether the property has explicit private(set) visibility.
     */
    public function is_private_set(): bool
    {
        return (bool) ($this->flags & Modifiers::PRIVATE_SET);
    }
    public function get_type(): string
    {
        return 'Stmt_Property';
    }
}