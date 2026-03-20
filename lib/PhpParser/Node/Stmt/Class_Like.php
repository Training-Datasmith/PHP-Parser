<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node;
use Php_Parser\Node\Property_Item;
abstract class Class_Like extends Node\Stmt
{
    /** @var Node\Identifier|null Name */
    public ?Node\Identifier $name = null;
    /** @var Node\Stmt[] Statements */
    public array $stmts;
    /** @var Node\AttributeGroup[] PHP attribute groups */
    public array $attr_groups;
    /** @var Node\Name|null Namespaced name (if using NameResolver) */
    public ?Node\Name $namespaced_name = null;
    /**
     * @return list<TraitUse>
     */
    public function get_trait_uses(): array
    {
        $trait_uses = [];
        foreach ($this->stmts as $stmt) {
            if ($stmt instanceof Trait_Use) {
                $trait_uses[] = $stmt;
            }
        }
        return $trait_uses;
    }
    /**
     * @return list<ClassConst>
     */
    public function get_constants(): array
    {
        $constants = [];
        foreach ($this->stmts as $stmt) {
            if ($stmt instanceof Class_Const) {
                $constants[] = $stmt;
            }
        }
        return $constants;
    }
    /**
     * @return list<Property>
     */
    public function get_properties(): array
    {
        $properties = [];
        foreach ($this->stmts as $stmt) {
            if ($stmt instanceof Property) {
                $properties[] = $stmt;
            }
        }
        return $properties;
    }
    /**
     * Gets property with the given name defined directly in this class/interface/trait.
     *
     * @param string $name Name of the property
     *
     * @return Property|null Property node or null if the property does not exist
     */
    public function get_property(string $name): ?Property
    {
        foreach ($this->stmts as $stmt) {
            if ($stmt instanceof Property) {
                foreach ($stmt->props as $prop) {
                    if ($prop instanceof Property_Item && $name === $prop->name->to_string()) {
                        return $stmt;
                    }
                }
            }
        }
        return null;
    }
    /**
     * Gets all methods defined directly in this class/interface/trait
     *
     * @return list<ClassMethod>
     */
    public function get_methods(): array
    {
        $methods = [];
        foreach ($this->stmts as $stmt) {
            if ($stmt instanceof Class_Method) {
                $methods[] = $stmt;
            }
        }
        return $methods;
    }
    /**
     * Gets method with the given name defined directly in this class/interface/trait.
     *
     * @param string $name Name of the method (compared case-insensitively)
     *
     * @return ClassMethod|null Method node or null if the method does not exist
     */
    public function get_method(string $name): ?Class_Method
    {
        $lower_name = strtolower($name);
        foreach ($this->stmts as $stmt) {
            if ($stmt instanceof Class_Method && $lower_name === $stmt->name->to_lower_string()) {
                return $stmt;
            }
        }
        return null;
    }
}