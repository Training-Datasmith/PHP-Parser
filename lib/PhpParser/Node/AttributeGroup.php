<?php

declare (strict_types=1);
namespace Php_Parser\Node;

use Php_Parser\Node_Abstract;
class Attribute_Group extends Node_Abstract
{
    /** @var Attribute[] Attributes */
    public array $attrs;
    /**
     * @param Attribute[] $attrs PHP attributes
     * @param array<string, mixed> $attributes Additional node attributes
     */
    public function __construct(array $attrs, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->attrs = $attrs;
    }
    public function get_sub_node_names(): array
    {
        return ['attrs'];
    }
    public function get_type(): string
    {
        return 'AttributeGroup';
    }
}