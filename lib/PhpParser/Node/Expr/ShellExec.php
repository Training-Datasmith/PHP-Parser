<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node\Expr;
use Php_Parser\Node\Interpolated_String_Part;
class Shell_Exec extends Expr
{
    /** @var (Expr|InterpolatedStringPart)[] Interpolated string array */
    public array $parts;
    /**
     * Constructs a shell exec (backtick) node.
     *
     * @param (Expr|InterpolatedStringPart)[] $parts Interpolated string array
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
        return 'Expr_ShellExec';
    }
}