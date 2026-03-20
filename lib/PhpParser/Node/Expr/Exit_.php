<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node\Expr;
class Exit_ extends Expr
{
    /* For use in "kind" attribute */
    public const KIND_EXIT = 1;
    public const KIND_DIE = 2;
    /** @var null|Expr Expression */
    public ?Expr $expr;
    /**
     * Constructs an exit() node.
     *
     * @param null|Expr $expr Expression
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(?Expr $expr = null, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->expr = $expr;
    }
    public function get_sub_node_names(): array
    {
        return ['expr'];
    }
    public function get_type(): string
    {
        return 'Expr_Exit';
    }
}