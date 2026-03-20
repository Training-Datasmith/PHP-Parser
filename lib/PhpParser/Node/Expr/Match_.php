<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node;
use Php_Parser\Node\Match_Arm;
class Match_ extends Node\Expr
{
    /** @var Node\Expr Condition */
    public Node\Expr $cond;
    /** @var MatchArm[] */
    public array $arms;
    /**
     * @param Node\Expr $cond Condition
     * @param MatchArm[] $arms
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Node\Expr $cond, array $arms = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->cond = $cond;
        $this->arms = $arms;
    }
    public function get_sub_node_names(): array
    {
        return ['cond', 'arms'];
    }
    public function get_type(): string
    {
        return 'Expr_Match';
    }
}