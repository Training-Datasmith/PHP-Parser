<?php

declare (strict_types=1);
namespace Php_Parser\Node;

use Php_Parser\Node;
interface Function_Like extends Node
{
    /**
     * Whether to return by reference
     */
    public function returns_by_ref(): bool;
    /**
     * List of parameters
     *
     * @return Param[]
     */
    public function get_params(): array;
    /**
     * Get the declared return type or null
     *
     * @return null|Identifier|Name|ComplexType
     */
    public function get_return_type();
    /**
     * The function body
     *
     * @return Stmt[]|null
     */
    public function get_stmts(): ?array;
    /**
     * Get PHP attribute groups.
     *
     * @return AttributeGroup[]
     */
    public function get_attr_groups(): array;
}