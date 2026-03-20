<?php

declare (strict_types=1);
namespace Php_Parser\Builder;

use Php_Parser;
use Php_Parser\Builder_Helpers;
use Php_Parser\Modifiers;
use Php_Parser\Node;
use Php_Parser\Node\Name;
use Php_Parser\Node\Stmt;
class Class_ extends Declaration
{
    protected string $name;
    protected ?Name $extends = null;
    /** @var list<Name> */
    protected array $implements = [];
    protected int $flags = 0;
    /** @var list<Stmt\TraitUse> */
    protected array $uses = [];
    /** @var list<Stmt\ClassConst> */
    protected array $constants = [];
    /** @var list<Stmt\Property> */
    protected array $properties = [];
    /** @var list<Stmt\ClassMethod> */
    protected array $methods = [];
    /** @var list<Node\AttributeGroup> */
    protected array $attribute_groups = [];
    /**
     * Creates a class builder.
     *
     * @param string $name Name of the class
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    /**
     * Extends a class.
     *
     * @param Name|string $class Name of class to extend
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function extend($class): self
    {
        $this->extends = Builder_Helpers::normalize_name($class);
        return $this;
    }
    /**
     * Implements one or more interfaces.
     *
     * @param Name|string ...$interfaces Names of interfaces to implement
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function implement(...$interfaces): self
    {
        foreach ($interfaces as $interface) {
            $this->implements[] = Builder_Helpers::normalize_name($interface);
        }
        return $this;
    }
    /**
     * Makes the class abstract.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_abstract(): self
    {
        $this->flags = Builder_Helpers::add_class_modifier($this->flags, Modifiers::ABSTRACT);
        return $this;
    }
    /**
     * Makes the class final.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_final(): self
    {
        $this->flags = Builder_Helpers::add_class_modifier($this->flags, Modifiers::FINAL);
        return $this;
    }
    /**
     * Makes the class readonly.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_readonly(): self
    {
        $this->flags = Builder_Helpers::add_class_modifier($this->flags, Modifiers::READONLY);
        return $this;
    }
    /**
     * Adds a statement.
     *
     * @param Stmt|PhpParser\Builder $stmt The statement to add
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function add_stmt($stmt)
    {
        $stmt = Builder_Helpers::normalize_node($stmt);
        if ($stmt instanceof Stmt\Property) {
            $this->properties[] = $stmt;
        } elseif ($stmt instanceof Stmt\Class_Method) {
            $this->methods[] = $stmt;
        } elseif ($stmt instanceof Stmt\Trait_Use) {
            $this->uses[] = $stmt;
        } elseif ($stmt instanceof Stmt\Class_Const) {
            $this->constants[] = $stmt;
        } else {
            throw new \LogicException(sprintf('Unexpected node of type "%s"', $stmt->get_type()));
        }
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
     * Returns the built class node.
     *
     * @return Stmt\Class_ The built class node
     */
    public function get_node(): Php_Parser\Node
    {
        return new Stmt\Class_($this->name, ['flags' => $this->flags, 'extends' => $this->extends, 'implements' => $this->implements, 'stmts' => array_merge($this->uses, $this->constants, $this->properties, $this->methods), 'attrGroups' => $this->attribute_groups], $this->attributes);
    }
}