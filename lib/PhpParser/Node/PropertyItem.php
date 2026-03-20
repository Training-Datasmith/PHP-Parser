<?php

declare (strict_types=1);
namespace Php_Parser\Node;

use Php_Parser\Node;
use Php_Parser\Node_Abstract;
class Property_Item extends Node_Abstract
{
    /** @var Node\VarLikeIdentifier Name */
    public Var_Like_Identifier $name;
    /** @var null|Node\Expr Default */
    public ?Expr $default;
    /**
     * Constructs a class property item node.
     *
     * @param string|Node\VarLikeIdentifier $name Name
     * @param null|Node\Expr $default Default value
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct($name, ?Node\Expr $default = null, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->name = \is_string($name) ? new Node\Var_Like_Identifier($name) : $name;
        $this->default = $default;
    }
    public function get_sub_node_names(): array
    {
        return ['name', 'default'];
    }
    public function get_type(): string
    {
        return 'PropertyItem';
    }
}
// @deprecated compatibility alias
class_alias(Property_Item::class, Stmt\Property_Property::class);