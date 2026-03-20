<?php

declare (strict_types=1);
namespace Php_Parser\Builder;

use Php_Parser\Builder;
use Php_Parser\Builder_Helpers;
use Php_Parser\Modifiers;
use Php_Parser\Node;
use Php_Parser\Node\Stmt;
class Trait_Use_Adaptation implements Builder
{
    private const TYPE_UNDEFINED = 0;
    private const TYPE_ALIAS = 1;
    private const TYPE_PRECEDENCE = 2;
    protected int $type;
    protected ?Node\Name $trait;
    protected Node\Identifier $method;
    protected ?int $modifier = null;
    protected ?Node\Identifier $alias = null;
    /** @var Node\Name[] */
    protected array $insteadof = [];
    /**
     * Creates a trait use adaptation builder.
     *
     * @param Node\Name|string|null $trait Name of adapted trait
     * @param Node\Identifier|string $method Name of adapted method
     */
    public function __construct($trait, $method)
    {
        $this->type = self::TYPE_UNDEFINED;
        $this->trait = is_null($trait) ? null : Builder_Helpers::normalize_name($trait);
        $this->method = Builder_Helpers::normalize_identifier($method);
    }
    /**
     * Sets alias of method.
     *
     * @param Node\Identifier|string $alias Alias for adapted method
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function as($alias): self
    {
        if ($this->type === self::TYPE_UNDEFINED) {
            $this->type = self::TYPE_ALIAS;
        }
        if ($this->type !== self::TYPE_ALIAS) {
            throw new \LogicException('Cannot set alias for not alias adaptation buider');
        }
        $this->alias = Builder_Helpers::normalize_identifier($alias);
        return $this;
    }
    /**
     * Sets adapted method public.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_public(): self
    {
        $this->set_modifier(Modifiers::PUBLIC);
        return $this;
    }
    /**
     * Sets adapted method protected.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_protected(): self
    {
        $this->set_modifier(Modifiers::PROTECTED);
        return $this;
    }
    /**
     * Sets adapted method private.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_private(): self
    {
        $this->set_modifier(Modifiers::PRIVATE);
        return $this;
    }
    /**
     * Adds overwritten traits.
     *
     * @param Node\Name|string ...$traits Traits for overwrite
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function insteadof(...$traits): self
    {
        if ($this->type === self::TYPE_UNDEFINED) {
            if (is_null($this->trait)) {
                throw new \LogicException('Precedence adaptation must have trait');
            }
            $this->type = self::TYPE_PRECEDENCE;
        }
        if ($this->type !== self::TYPE_PRECEDENCE) {
            throw new \LogicException('Cannot add overwritten traits for not precedence adaptation buider');
        }
        foreach ($traits as $trait) {
            $this->insteadof[] = Builder_Helpers::normalize_name($trait);
        }
        return $this;
    }
    protected function set_modifier(int $modifier): void
    {
        if ($this->type === self::TYPE_UNDEFINED) {
            $this->type = self::TYPE_ALIAS;
        }
        if ($this->type !== self::TYPE_ALIAS) {
            throw new \LogicException('Cannot set access modifier for not alias adaptation buider');
        }
        if (is_null($this->modifier)) {
            $this->modifier = $modifier;
        } else {
            throw new \LogicException('Multiple access type modifiers are not allowed');
        }
    }
    /**
     * Returns the built node.
     *
     * @return Node The built node
     */
    public function get_node(): Node
    {
        switch ($this->type) {
            case self::TYPE_ALIAS:
                return new Stmt\Trait_Use_Adaptation\Alias($this->trait, $this->method, $this->modifier, $this->alias);
            case self::TYPE_PRECEDENCE:
                return new Stmt\Trait_Use_Adaptation\Precedence($this->trait, $this->method, $this->insteadof);
            default:
                throw new \LogicException('Type of adaptation is not defined');
        }
    }
}