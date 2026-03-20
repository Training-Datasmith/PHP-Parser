<?php

declare (strict_types=1);
namespace Php_Parser\Node;

use Php_Parser\Node_Abstract;
/**
 * Represents the "..." in "foo(...)" of the first-class callable syntax.
 */
class Variadic_Placeholder extends Node_Abstract
{
    /**
     * Create a variadic argument placeholder (first-class callable syntax).
     *
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function get_type(): string
    {
        return 'VariadicPlaceholder';
    }
    public function get_sub_node_names(): array
    {
        return [];
    }
}