<?php

declare (strict_types=1);
namespace Php_Parser\Builder;

use Php_Parser\Builder;
use Php_Parser\Builder_Helpers;
use Php_Parser\Node;
use Php_Parser\Node\Stmt;
class Trait_Use implements Builder
{
    /** @var Node\Name[] */
    protected array $traits = [];
    /** @var Stmt\TraitUseAdaptation[] */
    protected array $adaptations = [];
    /**
     * Creates a trait use builder.
     *
     * @param Node\Name|string ...$traits Names of used traits
     */
    public function __construct(...$traits)
    {
        foreach ($traits as $trait) {
            $this->and($trait);
        }
    }
    /**
     * Adds used trait.
     *
     * @param Node\Name|string $trait Trait name
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function and($trait): self
    {
        $this->traits[] = Builder_Helpers::normalize_name($trait);
        return $this;
    }
    /**
     * Adds trait adaptation.
     *
     * @param Stmt\TraitUseAdaptation|Builder\TraitUseAdaptation $adaptation Trait adaptation
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function with($adaptation): self
    {
        $adaptation = Builder_Helpers::normalize_node($adaptation);
        if (!$adaptation instanceof Stmt\Trait_Use_Adaptation) {
            throw new \LogicException('Adaptation must have type TraitUseAdaptation');
        }
        $this->adaptations[] = $adaptation;
        return $this;
    }
    /**
     * Returns the built node.
     *
     * @return Node The built node
     */
    public function get_node(): Node
    {
        return new Stmt\Trait_Use($this->traits, $this->adaptations);
    }
}