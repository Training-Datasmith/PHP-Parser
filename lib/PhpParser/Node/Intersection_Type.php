<?php

declare (strict_types=1);
namespace Php_Parser\Node;

class Intersection_Type extends Complex_Type
{
    /** @var (Identifier|Name)[] Types */
    public array $types;
    /**
     * Constructs an intersection type.
     *
     * @param (Identifier|Name)[] $types Types
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(array $types, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->types = $types;
    }
    public function get_sub_node_names(): array
    {
        return ['types'];
    }
    public function get_type(): string
    {
        return 'IntersectionType';
    }
}