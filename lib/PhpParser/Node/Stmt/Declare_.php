<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node;
use Php_Parser\Node\Declare_Item;
class Declare_ extends Node\Stmt
{
    /** @var DeclareItem[] List of declares */
    public array $declares;
    /** @var Node\Stmt[]|null Statements */
    public ?array $stmts;
    /**
     * Constructs a declare node.
     *
     * @param DeclareItem[] $declares List of declares
     * @param Node\Stmt[]|null $stmts Statements
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(array $declares, ?array $stmts = null, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->declares = $declares;
        $this->stmts = $stmts;
    }
    public function get_sub_node_names(): array
    {
        return ['declares', 'stmts'];
    }
    public function get_type(): string
    {
        return 'Stmt_Declare';
    }
}