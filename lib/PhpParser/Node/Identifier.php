<?php

declare (strict_types=1);
namespace Php_Parser\Node;

use Php_Parser\Node_Abstract;
/**
 * Represents a non-namespaced name. Namespaced names are represented using Name nodes.
 */
class Identifier extends Node_Abstract
{
    /**
     * @psalm-var non-empty-string
     * @var string Identifier as string
     */
    public string $name;
    /** @var array<string, bool> */
    private static array $special_class_names = ['self' => true, 'parent' => true, 'static' => true];
    /**
     * Constructs an identifier node.
     *
     * @param string $name Identifier as string
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(string $name, array $attributes = [])
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Identifier name cannot be empty');
        }
        $this->attributes = $attributes;
        $this->name = $name;
    }
    public function get_sub_node_names(): array
    {
        return ['name'];
    }
    /**
     * Get identifier as string.
     *
     * @psalm-return non-empty-string
     * @return string Identifier as string.
     */
    public function to_string(): string
    {
        return $this->name;
    }
    /**
     * Get lowercased identifier as string.
     *
     * @psalm-return non-empty-string&lowercase-string
     * @return string Lowercased identifier as string
     */
    public function to_lower_string(): string
    {
        return strtolower($this->name);
    }
    /**
     * Checks whether the identifier is a special class name (self, parent or static).
     *
     * @return bool Whether identifier is a special class name
     */
    public function is_special_class_name(): bool
    {
        return isset(self::$special_class_names[strtolower($this->name)]);
    }
    /**
     * Get identifier as string.
     *
     * @psalm-return non-empty-string
     * @return string Identifier as string
     */
    public function __toString(): string
    {
        return $this->name;
    }
    public function get_type(): string
    {
        return 'Identifier';
    }
}