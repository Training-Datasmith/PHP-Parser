<?php

declare (strict_types=1);
namespace Php_Parser\Node;

class Union_Type extends Complex_Type
{
    /** @var (Identifier|Name|IntersectionType)[] Types */
    public array $types;
    /**
     * Constructs a union type.
     *
     * @param (Identifier|Name|IntersectionType)[] $types Types
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
        return 'UnionType';
    }
}