<?php

declare (strict_types=1);
namespace Php_Parser\Builder;

use Php_Parser;
use Php_Parser\Builder_Helpers;
use Php_Parser\Node;
use Php_Parser\Node\Name;
use Php_Parser\Node\Stmt;
class Interface_ extends Declaration
{
    protected string $name;
    /** @var list<Name> */
    protected array $extends = [];
    /** @var list<Stmt\ClassConst> */
    protected array $constants = [];
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
     * Extends one or more interfaces.
     *
     * @param Name|string ...$interfaces Names of interfaces to extend
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function extend(...$interfaces): self
    {
        foreach ($interfaces as $interface) {
            $this->extends[] = Builder_Helpers::normalize_name($interface);
        }
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
        if ($stmt instanceof Stmt\Class_Const) {
            $this->constants[] = $stmt;
        } elseif ($stmt instanceof Stmt\Class_Method) {
            // we erase all statements in the body of an interface method
            $stmt->stmts = null;
            $this->methods[] = $stmt;
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
     * Returns the built interface node.
     *
     * @return Stmt\Interface_ The built interface node
     */
    public function get_node(): Php_Parser\Node
    {
        return new Stmt\Interface_($this->name, ['extends' => $this->extends, 'stmts' => array_merge($this->constants, $this->methods), 'attrGroups' => $this->attribute_groups], $this->attributes);
    }
}