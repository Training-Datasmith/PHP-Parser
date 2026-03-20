<?php

declare (strict_types=1);
namespace Php_Parser\Node;

use Php_Parser\Node_Abstract;
class Closure_Use extends Node_Abstract
{
    /** @var Expr\Variable Variable to use */
    public Expr\Variable $var;
    /** @var bool Whether to use by reference */
    public bool $by_ref;
    /**
     * Constructs a closure use node.
     *
     * @param Expr\Variable $var Variable to use
     * @param bool $byRef Whether to use by reference
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Expr\Variable $var, bool $by_ref = false, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->var = $var;
        $this->by_ref = $by_ref;
    }
    public function get_sub_node_names(): array
    {
        return ['var', 'byRef'];
    }
    public function get_type(): string
    {
        return 'ClosureUse';
    }
}
// @deprecated compatibility alias
class_alias(Closure_Use::class, Expr\Closure_Use::class);