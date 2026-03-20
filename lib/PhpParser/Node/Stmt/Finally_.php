<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node;
class Finally_ extends Node\Stmt
{
    /** @var Node\Stmt[] Statements */
    public array $stmts;
    /**
     * Constructs a finally node.
     *
     * @param Node\Stmt[] $stmts Statements
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(array $stmts = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->stmts = $stmts;
    }
    public function get_sub_node_names(): array
    {
        return ['stmts'];
    }
    public function get_type(): string
    {
        return 'Stmt_Finally';
    }
}