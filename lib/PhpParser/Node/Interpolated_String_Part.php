<?php

declare (strict_types=1);
namespace Php_Parser\Node;

use Php_Parser\Node_Abstract;
class Interpolated_String_Part extends Node_Abstract
{
    /** @var string String value */
    public string $value;
    /**
     * Constructs a node representing a string part of an interpolated string.
     *
     * @param string $value String value
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(string $value, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->value = $value;
    }
    public function get_sub_node_names(): array
    {
        return ['value'];
    }
    public function get_type(): string
    {
        return 'InterpolatedStringPart';
    }
}
// @deprecated compatibility alias
class_alias(Interpolated_String_Part::class, Scalar\Encapsed_String_Part::class);