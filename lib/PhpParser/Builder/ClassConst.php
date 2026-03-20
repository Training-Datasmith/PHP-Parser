<?php

declare (strict_types=1);
namespace Php_Parser\Builder;

use Php_Parser;
use Php_Parser\Builder_Helpers;
use Php_Parser\Modifiers;
use Php_Parser\Node;
use Php_Parser\Node\Const_;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Stmt;
class Class_Const implements Php_Parser\Builder
{
    protected int $flags = 0;
    /** @var array<string, mixed> */
    protected array $attributes = [];
    /** @var list<Const_> */
    protected array $constants = [];
    /** @var list<Node\AttributeGroup> */
    protected array $attribute_groups = [];
    /** @var Identifier|Node\Name|Node\ComplexType|null */
    protected ?Node $type = null;
    /**
     * Creates a class constant builder
     *
     * @param string|Identifier $name Name
     * @param Node\Expr|bool|null|int|float|string|array|\UnitEnum $value Value
     */
    public function __construct($name, $value)
    {
        $this->constants = [new Const_($name, Builder_Helpers::normalize_value($value))];
    }
    /**
     * Add another constant to const group
     *
     * @param string|Identifier $name Name
     * @param Node\Expr|bool|null|int|float|string|array|\UnitEnum $value Value
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function add_const($name, $value): self
    {
        $this->constants[] = new Const_($name, Builder_Helpers::normalize_value($value));
        return $this;
    }
    /**
     * Makes the constant public.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_public(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::PUBLIC);
        return $this;
    }
    /**
     * Makes the constant protected.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_protected(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::PROTECTED);
        return $this;
    }
    /**
     * Makes the constant private.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_private(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::PRIVATE);
        return $this;
    }
    /**
     * Makes the constant final.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_final(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::FINAL);
        return $this;
    }
    /**
     * Sets doc comment for the constant.
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
     * Sets the constant type.
     *
     * @param string|Node\Name|Identifier|Node\ComplexType $type
     *
     * @return $this
     */
    public function set_type($type): self
    {
        $this->type = Builder_Helpers::normalize_type($type);
        return $this;
    }
    /**
     * Returns the built class node.
     *
     * @return Stmt\ClassConst The built constant node
     */
    public function get_node(): Php_Parser\Node
    {
        return new Stmt\Class_Const($this->constants, $this->flags, $this->attributes, $this->attribute_groups, $this->type);
    }
}