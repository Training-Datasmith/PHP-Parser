<?php

declare (strict_types=1);
namespace Php_Parser\Builder;

use Php_Parser;
use Php_Parser\Builder_Helpers;
use Php_Parser\Node;
use Php_Parser\Node\Stmt;
class Function_ extends Function_Like
{
    protected string $name;
    /** @var list<Stmt> */
    protected array $stmts = [];
    /** @var list<Node\AttributeGroup> */
    protected array $attribute_groups = [];
    /**
     * Creates a function builder.
     *
     * @param string $name Name of the function
     */
    public function __construct(string $name)
    {
        $this->name = $name;
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
     * Returns the built function node.
     *
     * @return Stmt\Function_ The built function node
     */
    public function get_node(): Node
    {
        return new Stmt\Function_($this->name, ['byRef' => $this->return_by_ref, 'params' => $this->params, 'returnType' => $this->return_type, 'stmts' => $this->stmts, 'attrGroups' => $this->attribute_groups], $this->attributes);
    }
}