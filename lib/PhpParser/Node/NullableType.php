<?php

declare (strict_types=1);
namespace Php_Parser\Node;

use Php_Parser\Node;
class Nullable_Type extends Complex_Type
{
    /** @var Identifier|Name Type */
    public Node $type;
    /**
     * Constructs a nullable type (wrapping another type).
     *
     * @param Identifier|Name $type Type
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Node $type, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->type = $type;
    }
    public function get_sub_node_names(): array
    {
        return ['type'];
    }
    public function get_type(): string
    {
        return 'NullableType';
    }
}