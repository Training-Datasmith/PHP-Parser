<?php

declare (strict_types=1);
namespace Php_Parser\Builder;

use Php_Parser;
use Php_Parser\Builder_Helpers;
use Php_Parser\Modifiers;
use Php_Parser\Node;
class Param implements Php_Parser\Builder
{
    protected string $name;
    protected ?Node\Expr $default = null;
    /** @var Node\Identifier|Node\Name|Node\ComplexType|null */
    protected ?Node $type = null;
    protected bool $by_ref = false;
    protected int $flags = 0;
    protected bool $variadic = false;
    /** @var list<Node\AttributeGroup> */
    protected array $attribute_groups = [];
    /**
     * Creates a parameter builder.
     *
     * @param string $name Name of the parameter
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    /**
     * Sets default value for the parameter.
     *
     * @param mixed $value Default value to use
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function set_default($value): self
    {
        $this->default = Builder_Helpers::normalize_value($value);
        return $this;
    }
    /**
     * Sets type for the parameter.
     *
     * @param string|Node\Name|Node\Identifier|Node\ComplexType $type Parameter type
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function set_type($type): self
    {
        $this->type = Builder_Helpers::normalize_type($type);
        if ($this->type == 'void') {
            throw new \LogicException('Parameter type cannot be void');
        }
        return $this;
    }
    /**
     * Make the parameter accept the value by reference.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_by_ref(): self
    {
        $this->by_ref = true;
        return $this;
    }
    /**
     * Make the parameter variadic
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_variadic(): self
    {
        $this->variadic = true;
        return $this;
    }
    /**
     * Makes the (promoted) parameter public.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_public(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::PUBLIC);
        return $this;
    }
    /**
     * Makes the (promoted) parameter protected.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_protected(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::PROTECTED);
        return $this;
    }
    /**
     * Makes the (promoted) parameter private.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_private(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::PRIVATE);
        return $this;
    }
    /**
     * Makes the (promoted) parameter readonly.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_readonly(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::READONLY);
        return $this;
    }
    /**
     * Gives the promoted property private(set) visibility.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_private_set(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::PRIVATE_SET);
        return $this;
    }
    /**
     * Gives the promoted property protected(set) visibility.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_protected_set(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::PROTECTED_SET);
        return $this;
    }
    /**
     * Adds an attribute group.
     *
     * @param Node\Attribute|Node\AttributeGroup $attribute
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function add_attribute($attribute): self
    {
        $this->attribute_groups[] = Builder_Helpers::normalize_attribute($attribute);
        return $this;
    }
    /**
     * Returns the built parameter node.
     *
     * @return Node\Param The built parameter node
     */
    public function get_node(): Node
    {
        return new Node\Param(new Node\Expr\Variable($this->name), $this->default, $this->type, $this->by_ref, $this->variadic, [], $this->flags, $this->attribute_groups);
    }
}