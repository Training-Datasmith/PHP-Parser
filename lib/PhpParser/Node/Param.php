<?php

declare (strict_types=1);
namespace Php_Parser\Node;

use Php_Parser\Modifiers;
use Php_Parser\Node;
use Php_Parser\Node_Abstract;
class Param extends Node_Abstract
{
    /** @var null|Identifier|Name|ComplexType Type declaration */
    public ?Node $type;
    /** @var bool Whether parameter is passed by reference */
    public bool $by_ref;
    /** @var bool Whether this is a variadic argument */
    public bool $variadic;
    /** @var Expr\Variable|Expr\Error Parameter variable */
    public Expr $var;
    /** @var null|Expr Default value */
    public ?Expr $default;
    /** @var int Optional visibility flags */
    public int $flags;
    /** @var AttributeGroup[] PHP attribute groups */
    public array $attr_groups;
    /** @var PropertyHook[] Property hooks for promoted properties */
    public array $hooks;
    /**
     * Constructs a parameter node.
     *
     * @param Expr\Variable|Expr\Error $var Parameter variable
     * @param null|Expr $default Default value
     * @param null|Identifier|Name|ComplexType $type Type declaration
     * @param bool $byRef Whether is passed by reference
     * @param bool $variadic Whether this is a variadic argument
     * @param array<string, mixed> $attributes Additional attributes
     * @param int $flags Optional visibility flags
     * @param list<AttributeGroup> $attrGroups PHP attribute groups
     * @param PropertyHook[] $hooks Property hooks for promoted properties
     */
    public function __construct(Expr $var, ?Expr $default = null, ?Node $type = null, bool $by_ref = false, bool $variadic = false, array $attributes = [], int $flags = 0, array $attr_groups = [], array $hooks = [])
    {
        $this->attributes = $attributes;
        $this->type = $type;
        $this->by_ref = $by_ref;
        $this->variadic = $variadic;
        $this->var = $var;
        $this->default = $default;
        $this->flags = $flags;
        $this->attr_groups = $attr_groups;
        $this->hooks = $hooks;
    }
    public function get_sub_node_names(): array
    {
        return ['attrGroups', 'flags', 'type', 'byRef', 'variadic', 'var', 'default', 'hooks'];
    }
    public function get_type(): string
    {
        return 'Param';
    }
    /**
     * Whether this parameter uses constructor property promotion.
     */
    public function is_promoted(): bool
    {
        return $this->flags !== 0 || $this->hooks !== [];
    }
    public function is_final(): bool
    {
        return (bool) ($this->flags & Modifiers::FINAL);
    }
    public function is_public(): bool
    {
        $public = (bool) ($this->flags & Modifiers::PUBLIC);
        if ($public) {
            return true;
        }
        if (!$this->is_promoted()) {
            return false;
        }
        return ($this->flags & Modifiers::VISIBILITY_MASK) === 0;
    }
    public function is_protected(): bool
    {
        return (bool) ($this->flags & Modifiers::PROTECTED);
    }
    public function is_private(): bool
    {
        return (bool) ($this->flags & Modifiers::PRIVATE);
    }
    public function is_readonly(): bool
    {
        return (bool) ($this->flags & Modifiers::READONLY);
    }
    /**
     * Whether the promoted property has explicit public(set) visibility.
     */
    public function is_public_set(): bool
    {
        return (bool) ($this->flags & Modifiers::PUBLIC_SET);
    }
    /**
     * Whether the promoted property has explicit protected(set) visibility.
     */
    public function is_protected_set(): bool
    {
        return (bool) ($this->flags & Modifiers::PROTECTED_SET);
    }
    /**
     * Whether the promoted property has explicit private(set) visibility.
     */
    public function is_private_set(): bool
    {
        return (bool) ($this->flags & Modifiers::PRIVATE_SET);
    }
}