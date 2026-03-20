<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node;
class Return_ extends Node\Stmt
{
    /** @var null|Node\Expr Expression */
    public ?Node\Expr $expr;
    /**
     * Constructs a return node.
     *
     * @param null|Node\Expr $expr Expression
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(?Node\Expr $expr = null, array $attributes = [])
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
        return 'Stmt_Return';
    }
}