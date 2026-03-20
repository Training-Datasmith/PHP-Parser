<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt\Trait_Use_Adaptation;

use Php_Parser\Node;
class Alias extends Node\Stmt\Trait_Use_Adaptation
{
    /** @var null|int New modifier */
    public ?int $new_modifier;
    /** @var null|Node\Identifier New name */
    public ?Node\Identifier $new_name;
    /**
     * Constructs a trait use precedence adaptation node.
     *
     * @param null|Node\Name $trait Trait name
     * @param string|Node\Identifier $method Method name
     * @param null|int $newModifier New modifier
     * @param null|string|Node\Identifier $newName New name
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(?Node\Name $trait, $method, ?int $new_modifier, $new_name, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->trait = $trait;
        $this->method = \is_string($method) ? new Node\Identifier($method) : $method;
        $this->new_modifier = $new_modifier;
        $this->new_name = \is_string($new_name) ? new Node\Identifier($new_name) : $new_name;
    }
    public function get_sub_node_names(): array
    {
        return ['trait', 'method', 'newModifier', 'newName'];
    }
    public function get_type(): string
    {
        return 'Stmt_TraitUseAdaptation_Alias';
    }
}