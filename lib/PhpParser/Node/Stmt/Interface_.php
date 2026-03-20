<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node;
class Interface_ extends Class_Like
{
    /** @var Node\Name[] Extended interfaces */
    public array $extends;
    /**
     * Constructs a class node.
     *
     * @param string|Node\Identifier $name Name
     * @param array{
     *     extends?: Node\Name[],
     *     stmts?: Node\Stmt[],
     *     attrGroups?: Node\AttributeGroup[],
     * } $subNodes Array of the following optional subnodes:
     *             'extends'    => array(): Name of extended interfaces
     *             'stmts'      => array(): Statements
     *             'attrGroups' => array(): PHP attribute groups
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct($name, array $sub_nodes = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->name = \is_string($name) ? new Node\Identifier($name) : $name;
        $this->extends = $sub_nodes['extends'] ?? [];
        $this->stmts = $sub_nodes['stmts'] ?? [];
        $this->attr_groups = $sub_nodes['attrGroups'] ?? [];
    }
    public function get_sub_node_names(): array
    {
        return ['attrGroups', 'name', 'extends', 'stmts'];
    }
    public function get_type(): string
    {
        return 'Stmt_Interface';
    }
}