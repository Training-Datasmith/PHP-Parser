<?php

declare (strict_types=1);
namespace Php_Parser\Node;

use Php_Parser\Node_Abstract;
class Name extends Node_Abstract
{
    /**
     * @psalm-var non-empty-string
     * @var string Name as string
     */
    public string $name;
    /** @var array<string, bool> */
    private static array $special_class_names = ['self' => true, 'parent' => true, 'static' => true];
    /**
     * Constructs a name node.
     *
     * @param string|string[]|self $name Name as string, part array or Name instance (copy ctor)
     * @param array<string, mixed> $attributes Additional attributes
     */
    final public function __construct($name, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->name = self::prepare_name($name);
    }
    public function get_sub_node_names(): array
    {
        return ['name'];
    }
    /**
     * Get parts of name (split by the namespace separator).
     *
     * @psalm-return non-empty-list<string>
     * @return string[] Parts of name
     */
    public function get_parts(): array
    {
        return \explode('\\', $this->name);
    }
    /**
     * Gets the first part of the name, i.e. everything before the first namespace separator.
     *
     * @return string First part of the name
     */
    public function get_first(): string
    {
        if (false !== $pos = \strpos($this->name, '\\')) {
            return \substr($this->name, 0, $pos);
        }
        return $this->name;
    }
    /**
     * Gets the last part of the name, i.e. everything after the last namespace separator.
     *
     * @return string Last part of the name
     */
    public function get_last(): string
    {
        if (false !== $pos = \strrpos($this->name, '\\')) {
            return \substr($this->name, $pos + 1);
        }
        return $this->name;
    }
    /**
     * Checks whether the name is unqualified. (E.g. Name)
     *
     * @return bool Whether the name is unqualified
     */
    public function is_unqualified(): bool
    {
        return false === \strpos($this->name, '\\');
    }
    /**
     * Checks whether the name is qualified. (E.g. Name\Name)
     *
     * @return bool Whether the name is qualified
     */
    public function is_qualified(): bool
    {
        return false !== \strpos($this->name, '\\');
    }
    /**
     * Checks whether the name is fully qualified. (E.g. \Name)
     *
     * @return bool Whether the name is fully qualified
     */
    public function is_fully_qualified(): bool
    {
        return false;
    }
    /**
     * Checks whether the name is explicitly relative to the current namespace. (E.g. namespace\Name)
     *
     * @return bool Whether the name is relative
     */
    public function is_relative(): bool
    {
        return false;
    }
    /**
     * Returns a string representation of the name itself, without taking the name type into
     * account (e.g., not including a leading backslash for fully qualified names).
     *
     * @psalm-return non-empty-string
     * @return string String representation
     */
    public function to_string(): string
    {
        return $this->name;
    }
    /**
     * Returns a string representation of the name as it would occur in code (e.g., including
     * leading backslash for fully qualified names.
     *
     * @psalm-return non-empty-string
     * @return string String representation
     */
    public function to_code_string(): string
    {
        return $this->to_string();
    }
    /**
     * Returns lowercased string representation of the name, without taking the name type into
     * account (e.g., no leading backslash for fully qualified names).
     *
     * @psalm-return non-empty-string&lowercase-string
     * @return string Lowercased string representation
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
     * Returns a string representation of the name by imploding the namespace parts with the
     * namespace separator.
     *
     * @psalm-return non-empty-string
     * @return string String representation
     */
    public function __toString(): string
    {
        return $this->name;
    }
    /**
     * Gets a slice of a name (similar to array_slice).
     *
     * This method returns a new instance of the same type as the original and with the same
     * attributes.
     *
     * If the slice is empty, null is returned. The null value will be correctly handled in
     * concatenations using concat().
     *
     * Offset and length have the same meaning as in array_slice().
     *
     * @param int $offset Offset to start the slice at (may be negative)
     * @param int|null $length Length of the slice (may be negative)
     *
     * @return static|null Sliced name
     */
    public function slice(int $offset, ?int $length = null): ?self
    {
        if ($offset === 1 && $length === null) {
            // Short-circuit the common case.
            if (false !== $pos = \strpos($this->name, '\\')) {
                return new static(\substr($this->name, $pos + 1));
            }
            return null;
        }
        $parts = \explode('\\', $this->name);
        $num_parts = \count($parts);
        $real_offset = $offset < 0 ? $offset + $num_parts : $offset;
        if ($real_offset < 0 || $real_offset > $num_parts) {
            throw new \OutOfBoundsException(sprintf('Offset %d is out of bounds', $offset));
        }
        if (null === $length) {
            $real_length = $num_parts - $real_offset;
        } else {
            $real_length = $length < 0 ? $length + $num_parts - $real_offset : $length;
            if ($real_length < 0 || $real_length > $num_parts - $real_offset) {
                throw new \OutOfBoundsException(sprintf('Length %d is out of bounds', $length));
            }
        }
        if ($real_length === 0) {
            // Empty slice is represented as null
            return null;
        }
        return new static(array_slice($parts, $real_offset, $real_length), $this->attributes);
    }
    /**
     * Concatenate two names, yielding a new Name instance.
     *
     * The type of the generated instance depends on which class this method is called on, for
     * example Name\FullyQualified::concat() will yield a Name\FullyQualified instance.
     *
     * If one of the arguments is null, a new instance of the other name will be returned. If both
     * arguments are null, null will be returned. As such, writing
     *     Name::concat($namespace, $shortName)
     * where $namespace is a Name node or null will work as expected.
     *
     * @param string|string[]|self|null $name1 The first name
     * @param string|string[]|self|null $name2 The second name
     * @param array<string, mixed> $attributes Attributes to assign to concatenated name
     *
     * @return static|null Concatenated name
     */
    public static function concat($name1, $name2, array $attributes = []): ?self
    {
        if (null === $name1 && null === $name2) {
            return null;
        }
        if (null === $name1) {
            return new static($name2, $attributes);
        }
        if (null === $name2) {
            return new static($name1, $attributes);
        }
        return new static(self::prepare_name($name1) . '\\' . self::prepare_name($name2), $attributes);
    }
    /**
     * Prepares a (string, array or Name node) name for use in name changing methods by converting
     * it to a string.
     *
     * @param string|string[]|self $name Name to prepare
     *
     * @psalm-return non-empty-string
     * @return string Prepared name
     */
    private static function prepare_name($name): string
    {
        if (\is_string($name)) {
            if ('' === $name) {
                throw new \InvalidArgumentException('Name cannot be empty');
            }
            return $name;
        }
        if (\is_array($name)) {
            if (empty($name)) {
                throw new \InvalidArgumentException('Name cannot be empty');
            }
            return implode('\\', $name);
        }
        if ($name instanceof self) {
            return $name->name;
        }
        throw new \InvalidArgumentException('Expected string, array of parts or Name instance');
    }
    public function get_type(): string
    {
        return 'Name';
    }
}