<?php

declare (strict_types=1);
namespace Php_Parser\Node;

use Php_Parser\Node;
use Php_Parser\Node_Abstract;
class Match_Arm extends Node_Abstract
{
    /** @var null|list<Node\Expr> */
    public ?array $conds;
    public Expr $body;
    /**
     * @param null|list<Node\Expr> $conds
     */
    public function __construct(?array $conds, Node\Expr $body, array $attributes = [])
    {
        $this->conds = $conds;
        $this->body = $body;
        $this->attributes = $attributes;
    }
    public function get_sub_node_names(): array
    {
        return ['conds', 'body'];
    }
    public function get_type(): string
    {
        return 'MatchArm';
    }
}