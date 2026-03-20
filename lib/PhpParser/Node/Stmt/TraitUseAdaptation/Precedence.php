<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt\Trait_Use_Adaptation;

use Php_Parser\Node;
class Precedence extends Node\Stmt\Trait_Use_Adaptation
{
    /** @var Node\Name[] Overwritten traits */
    public array $insteadof;
    /**
     * Constructs a trait use precedence adaptation node.
     *
     * @param Node\Name $trait Trait name
     * @param string|Node\Identifier $method Method name
     * @param Node\Name[] $insteadof Overwritten traits
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Node\Name $trait, $method, array $insteadof, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->trait = $trait;
        $this->method = \is_string($method) ? new Node\Identifier($method) : $method;
        $this->insteadof = $insteadof;
    }
    public function get_sub_node_names(): array
    {
        return ['trait', 'method', 'insteadof'];
    }
    public function get_type(): string
    {
        return 'Stmt_TraitUseAdaptation_Precedence';
    }
}