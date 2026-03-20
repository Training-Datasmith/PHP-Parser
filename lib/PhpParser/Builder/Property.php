<?php

declare (strict_types=1);
namespace Php_Parser\Builder;

use Php_Parser;
use Php_Parser\Builder_Helpers;
use Php_Parser\Modifiers;
use Php_Parser\Node;
use Php_Parser\Node\Complex_Type;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Stmt;
class Property implements Php_Parser\Builder
{
    protected string $name;
    protected int $flags = 0;
    protected ?Node\Expr $default = null;
    /** @var array<string, mixed> */
    protected array $attributes = [];
    /** @var null|Identifier|Name|ComplexType */
    protected ?Node $type = null;
    /** @var list<Node\AttributeGroup> */
    protected array $attribute_groups = [];
    /** @var list<Node\PropertyHook> */
    protected array $hooks = [];
    /**
     * Creates a property builder.
     *
     * @param string $name Name of the property
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    /**
     * Makes the property public.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_public(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::PUBLIC);
        return $this;
    }
    /**
     * Makes the property protected.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_protected(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::PROTECTED);
        return $this;
    }
    /**
     * Makes the property private.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_private(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::PRIVATE);
        return $this;
    }
    /**
     * Makes the property static.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_static(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::STATIC);
        return $this;
    }
    /**
     * Makes the property readonly.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_readonly(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::READONLY);
        return $this;
    }
    /**
     * Makes the property abstract. Requires at least one property hook to be specified as well.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_abstract(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::ABSTRACT);
        return $this;
    }
    /**
     * Makes the property final.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_final(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::FINAL);
        return $this;
    }
    /**
     * Gives the property private(set) visibility.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_private_set(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::PRIVATE_SET);
        return $this;
    }
    /**
     * Gives the property protected(set) visibility.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_protected_set(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::PROTECTED_SET);
        return $this;
    }
    /**
     * Sets default value for the property.
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
     * Sets doc comment for the property.
     *
     * @param PhpParser\Comment\Doc|string $docComment Doc comment to set
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function set_doc_comment($doc_comment): self
    {
        $this->attributes = ['comments' => [Builder_Helpers::normalize_doc_comment($doc_comment)]];
        return $this;
    }
    /**
     * Sets the property type for PHP 7.4+.
     *
     * @param string|Name|Identifier|ComplexType $type
     *
     * @return $this
     */
    public function set_type($type): self
    {
        $this->type = Builder_Helpers::normalize_type($type);
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
     * Adds a property hook.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function add_hook(Node\Property_Hook $hook): self
    {
        $this->hooks[] = $hook;
        return $this;
    }
    /**
     * Returns the built class node.
     *
     * @return Stmt\Property The built property node
     */
    public function get_node(): Php_Parser\Node
    {
        if ($this->flags & Modifiers::ABSTRACT && !$this->hooks) {
            throw new Php_Parser\Error('Only hooked properties may be declared abstract');
        }
        return new Stmt\Property($this->flags !== 0 ? $this->flags : Modifiers::PUBLIC, [new Node\Property_Item($this->name, $this->default)], $this->attributes, $this->type, $this->attribute_groups, $this->hooks);
    }
}