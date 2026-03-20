<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node\Identifier;
use Php_Parser\Node\Stmt;
class Label extends Stmt
{
    /** @var Identifier Name */
    public Identifier $name;
    /**
     * Constructs a label node.
     *
     * @param string|Identifier $name Name
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct($name, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->name = \is_string($name) ? new Identifier($name) : $name;
    }
    public function get_sub_node_names(): array
    {
        return ['name'];
    }
    public function get_type(): string
    {
        return 'Stmt_Label';
    }
}