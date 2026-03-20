<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node;
class Trait_Use extends Node\Stmt
{
    /** @var Node\Name[] Traits */
    public array $traits;
    /** @var TraitUseAdaptation[] Adaptations */
    public array $adaptations;
    /**
     * Constructs a trait use node.
     *
     * @param Node\Name[] $traits Traits
     * @param TraitUseAdaptation[] $adaptations Adaptations
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(array $traits, array $adaptations = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->traits = $traits;
        $this->adaptations = $adaptations;
    }
    public function get_sub_node_names(): array
    {
        return ['traits', 'adaptations'];
    }
    public function get_type(): string
    {
        return 'Stmt_TraitUse';
    }
}