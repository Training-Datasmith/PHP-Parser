<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
class Catch_ extends Node\Stmt
{
    /** @var Node\Name[] Types of exceptions to catch */
    public array $types;
    /** @var Expr\Variable|null Variable for exception */
    public ?Expr\Variable $var;
    /** @var Node\Stmt[] Statements */
    public array $stmts;
    /**
     * Constructs a catch node.
     *
     * @param Node\Name[] $types Types of exceptions to catch
     * @param Expr\Variable|null $var Variable for exception
     * @param Node\Stmt[] $stmts Statements
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(array $types, ?Expr\Variable $var = null, array $stmts = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->types = $types;
        $this->var = $var;
        $this->stmts = $stmts;
    }
    public function get_sub_node_names(): array
    {
        return ['types', 'var', 'stmts'];
    }
    public function get_type(): string
    {
        return 'Stmt_Catch';
    }
}