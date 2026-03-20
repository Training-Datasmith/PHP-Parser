<?php

declare (strict_types=1);
namespace Php_Parser\Builder;

use Php_Parser;
use Php_Parser\Builder_Helpers;
use Php_Parser\Node;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Stmt;
class Enum_ extends Declaration
{
    protected string $name;
    protected ?Identifier $scalar_type = null;
    /** @var list<Name> */
    protected array $implements = [];
    /** @var list<Stmt\TraitUse> */
    protected array $uses = [];
    /** @var list<Stmt\EnumCase> */
    protected array $enum_cases = [];
    /** @var list<Stmt\ClassConst> */
    protected array $constants = [];
    /** @var list<Stmt\ClassMethod> */
    protected array $methods = [];
    /** @var list<Node\AttributeGroup> */
    protected array $attribute_groups = [];
    /**
     * Creates an enum builder.
     *
     * @param string $name Name of the enum
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    /**
     * Sets the scalar type.
     *
     * @param string|Identifier $scalarType
     *
     * @return $this
     */
    public function set_scalar_type($scalar_type): self
    {
        $this->scalar_type = Builder_Helpers::normalize_type($scalar_type);
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
     * Adds a statement.
     *
     * @param Stmt|PhpParser\Builder $stmt The statement to add
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function add_stmt($stmt)
    {
        $stmt = Builder_Helpers::normalize_node($stmt);
        if ($stmt instanceof Stmt\Enum_Case) {
            $this->enum_cases[] = $stmt;
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
     * @return Stmt\Enum_ The built enum node
     */
    public function get_node(): Php_Parser\Node
    {
        return new Stmt\Enum_($this->name, ['scalarType' => $this->scalar_type, 'implements' => $this->implements, 'stmts' => array_merge($this->uses, $this->enum_cases, $this->constants, $this->methods), 'attrGroups' => $this->attribute_groups], $this->attributes);
    }
}