<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node\Expr;
class Isset_ extends Expr
{
    /** @var Expr[] Variables */
    public array $vars;
    /**
     * Constructs an array node.
     *
     * @param Expr[] $vars Variables
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(array $vars, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->vars = $vars;
    }
    public function get_sub_node_names(): array
    {
        return ['vars'];
    }
    public function get_type(): string
    {
        return 'Expr_Isset';
    }
}