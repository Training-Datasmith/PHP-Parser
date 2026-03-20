<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node\Static_Var;
use Php_Parser\Node\Stmt;
class Static_ extends Stmt
{
    /** @var StaticVar[] Variable definitions */
    public array $vars;
    /**
     * Constructs a static variables list node.
     *
     * @param StaticVar[] $vars Variable definitions
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
        return 'Stmt_Static';
    }
}