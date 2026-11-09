<?php

declare (strict_types=1);
namespace Php_Parser\Builder;

use Php_Parser;
use Php_Parser\Builder_Helpers;
use Php_Parser\Node;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Stmt;
class Enum_Case implements Php_Parser\Builder
{
    /** @var Identifier|string */
    protected $name;
    protected ?Node\Expr $value = null;
    /** @var array<string, mixed> */
    protected array $attributes = [];
    /** @var list<Node\AttributeGroup> */
    protected array $attribute_groups = [];
    /**
     * Creates an enum case builder.
     *
     * @param string|Identifier $name Name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }
    /**
     * Sets the value.
     *
     * @param Node\Expr|string|int $value
     *
     * @return $this
     */
    public function set_value($value): self
    {
        $this->value = Builder_Helpers::normalize_value($value);
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
     * Returns the built enum case node.
     *
     * @return Stmt\EnumCase The built constant node
     */
    public function get_node(): Php_Parser\Node
    {
        return new Stmt\Enum_Case($this->name, $this->value, $this->attribute_groups, $this->attributes);
    }
}