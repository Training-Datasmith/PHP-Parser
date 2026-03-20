<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node\Stmt;
class Inline_Html extends Stmt
{
    /** @var string String */
    public string $value;
    /**
     * Constructs an inline HTML node.
     *
     * @param string $value String
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(string $value, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->value = $value;
    }
    public function get_sub_node_names(): array
    {
        return ['value'];
    }
    public function get_type(): string
    {
        return 'Stmt_InlineHTML';
    }
}