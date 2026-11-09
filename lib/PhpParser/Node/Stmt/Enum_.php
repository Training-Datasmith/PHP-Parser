<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node;
class Enum_ extends Class_Like
{
    /** @var null|Node\Identifier Scalar Type */
    public ?Node $scalar_type;
    /** @var Node\Name[] Names of implemented interfaces */
    public array $implements;
    /**
     * @param string|Node\Identifier|null $name Name
     * @param array{
     *     scalarType?: Node\Identifier|null,
     *     implements?: Node\Name[],
     *     stmts?: Node\Stmt[],
     *     attrGroups?: Node\AttributeGroup[],
     * } $subNodes Array of the following optional subnodes:
     *             'scalar_type'  => null    : Scalar type
     *             'implements'  => array() : Names of implemented interfaces
     *             'stmts'       => array() : Statements
     *             'attr_groups'  => array() : PHP attribute groups
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct($name, array $sub_nodes = [], array $attributes = [])
    {
        $this->name = \is_string($name) ? new Node\Identifier($name) : $name;
        $this->scalar_type = $sub_nodes['scalar_type'] ?? null;
        $this->implements = $sub_nodes['implements'] ?? [];
        $this->stmts = $sub_nodes['stmts'] ?? [];
        $this->attr_groups = $sub_nodes['attr_groups'] ?? [];
        parent::__construct($attributes);
    }
    public function get_sub_node_names(): array
    {
        return ['attr_groups', 'name', 'scalar_type', 'implements', 'stmts'];
    }
    public function get_type(): string {
        return 'Stmt_Enum_';
    }
}