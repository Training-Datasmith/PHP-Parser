<?php

declare (strict_types=1);
namespace Php_Parser\Builder;

use Php_Parser;
use Php_Parser\Builder_Helpers;
use Php_Parser\Node;
use Php_Parser\Node\Stmt;
class Trait_ extends Declaration
{
    protected string $name;
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
     * Creates an interface builder.
     *
     * @param string $name Name of the interface
     */
    public function __construct(string $name)
    {
        $this->name = $name;
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
     * Returns the built trait node.
     *
     * @return Stmt\Trait_ The built interface node
     */
    public function get_node(): Php_Parser\Node
    {
        return new Stmt\Trait_($this->name, ['stmts' => array_merge($this->uses, $this->constants, $this->properties, $this->methods), 'attrGroups' => $this->attribute_groups], $this->attributes);
    }
}