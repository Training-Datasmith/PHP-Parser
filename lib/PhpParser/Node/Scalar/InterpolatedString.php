<?php

declare (strict_types=1);
namespace Php_Parser\Node\Scalar;

use Php_Parser\Node\Expr;
use Php_Parser\Node\Interpolated_String_Part;
use Php_Parser\Node\Scalar;
class Interpolated_String extends Scalar
{
    /** @var (Expr|InterpolatedStringPart)[] list of string parts */
    public array $parts;
    /**
     * Constructs an interpolated string node.
     *
     * @param (Expr|InterpolatedStringPart)[] $parts Interpolated string parts
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(array $parts, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->parts = $parts;
    }
    public function get_sub_node_names(): array
    {
        return ['parts'];
    }
    public function get_type(): string
    {
        return 'Scalar_InterpolatedString';
    }
}
// @deprecated compatibility alias
class_alias(Interpolated_String::class, Encapsed::class);