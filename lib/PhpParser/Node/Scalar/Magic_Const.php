<?php

declare (strict_types=1);
namespace Php_Parser\Node\Scalar;

use Php_Parser\Node\Scalar;
abstract class Magic_Const extends Scalar
{
    /**
     * Constructs a magic constant node.
     *
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function get_sub_node_names(): array
    {
        return [];
    }
    /**
     * Get name of magic constant.
     *
     * @return string Name of magic constant
     */
    abstract public function get_name(): string;
}