<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node\Stmt;
class Block extends Stmt
{
    /** @var Stmt[] Statements */
    public array $stmts;
    /**
     * A block of statements.
     *
     * @param Stmt[] $stmts Statements
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(array $stmts, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->stmts = $stmts;
    }
    public function get_type(): string
    {
        return 'Stmt_Block';
    }
    public function get_sub_node_names(): array
    {
        return ['stmts'];
    }
}