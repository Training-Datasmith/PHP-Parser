<?php

declare (strict_types=1);
namespace Php_Parser\Builder;

use Php_Parser;
use Php_Parser\Builder_Helpers;
use Php_Parser\Modifiers;
use Php_Parser\Node;
use Php_Parser\Node\Stmt;
class Method extends Function_Like
{
    protected string $name;
    protected int $flags = 0;
    /** @var list<Stmt>|null */
    protected ?array $stmts = [];
    /** @var list<Node\AttributeGroup> */
    protected array $attribute_groups = [];
    /**
     * Creates a method builder.
     *
     * @param string $name Name of the method
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    /**
     * Makes the method public.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_public(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::PUBLIC);
        return $this;
    }
    /**
     * Makes the method protected.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_protected(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::PROTECTED);
        return $this;
    }
    /**
     * Makes the method private.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_private(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::PRIVATE);
        return $this;
    }
    /**
     * Makes the method static.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_static(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::STATIC);
        return $this;
    }
    /**
     * Makes the method abstract.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_abstract(): self
    {
        if (!empty($this->stmts)) {
            throw new \LogicException('Cannot make method with statements abstract');
        }
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::ABSTRACT);
        $this->stmts = null;
        // abstract methods don't have statements
        return $this;
    }
    /**
     * Makes the method final.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_final(): self
    {
        $this->flags = Builder_Helpers::add_modifier($this->flags, Modifiers::FINAL);
        return $this;
    }
    /**
     * Adds a statement.
     *
     * @param Node|PhpParser\Builder $stmt The statement to add
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function add_stmt($stmt)
    {
        if (null === $this->stmts) {
            throw new \LogicException('Cannot add statements to an abstract method');
        }
        $this->stmts[] = Builder_Helpers::normalize_stmt($stmt);
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
     * Returns the built method node.
     *
     * @return Stmt\ClassMethod The built method node
     */
    public function get_node(): Node
    {
        return new Stmt\Class_Method($this->name, ['flags' => $this->flags, 'byRef' => $this->return_by_ref, 'params' => $this->params, 'returnType' => $this->return_type, 'stmts' => $this->stmts, 'attrGroups' => $this->attribute_groups], $this->attributes);
    }
}