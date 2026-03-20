<?php

declare (strict_types=1);
namespace Php_Parser\Node;

use Php_Parser\Node;
use Php_Parser\Node_Abstract;
class Static_Var extends Node_Abstract
{
    /** @var Expr\Variable Variable */
    public Expr\Variable $var;
    /** @var null|Node\Expr Default value */
    public ?Expr $default;
    /**
     * Constructs a static variable node.
     *
     * @param Expr\Variable $var Name
     * @param null|Node\Expr $default Default value
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Expr\Variable $var, ?Node\Expr $default = null, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->var = $var;
        $this->default = $default;
    }
    public function get_sub_node_names(): array
    {
        return ['var', 'default'];
    }
    public function get_type(): string
    {
        return 'StaticVar';
    }
}
// @deprecated compatibility alias
class_alias(Static_Var::class, Stmt\Static_Var::class);