<?php

declare (strict_types=1);
namespace Php_Parser;

/**
 * A PHP version, representing only the major and minor version components.
 */
class Php_Version
{
    /** @var int Version ID in PHP_VERSION_ID format */
    public int $id;
    /** @var int[] Minimum versions for builtin types */
    private const BUILTIN_TYPE_VERSIONS = ['array' => 50100, 'callable' => 50400, 'bool' => 70000, 'int' => 70000, 'float' => 70000, 'string' => 70000, 'iterable' => 70100, 'void' => 70100, 'object' => 70200, 'null' => 80000, 'false' => 80000, 'mixed' => 80000, 'never' => 80100, 'true' => 80200];
    private function __construct(int $id)
    {
        $this->id = $id;
    }
    /**
     * Create a PhpVersion object from major and minor version components.
     */
    public static function from_components(int $major, int $minor): self
    {
        return new self($major * 10000 + $minor * 100);
    }
    /**
     * Get the newest PHP version supported by this library. Support for this version may be partial,
     * if it is still under development.
     */
    public static function get_newest_supported(): self
    {
        return self::from_components(8, 5);
    }
    /**
     * Get the host PHP version, that is the PHP version we're currently running on.
     */
    public static function get_host_version(): self
    {
        return self::from_components(\PHP_MAJOR_VERSION, \PHP_MINOR_VERSION);
    }
    /**
     * Parse the version from a string like "8.1".
     */
    public static function from_string(string $version): self
    {
        if (!preg_match('/^(\d+)\.(\d+)/', $version, $matches)) {
            throw new \LogicException("Invalid PHP version \"{$version}\"");
        }
        return self::from_components((int) $matches[1], (int) $matches[2]);
    }
    /**
     * Check whether two versions are the same.
     */
    public function equals(Php_Version $other): bool
    {
        return $this->id === $other->id;
    }
    /**
     * Check whether this version is greater than or equal to the argument.
     */
    public function newer_or_equal(Php_Version $other): bool
    {
        return $this->id >= $other->id;
    }
    /**
     * Check whether this version is older than the argument.
     */
    public function older(Php_Version $other): bool
    {
        return $this->id < $other->id;
    }
    /**
     * Check whether this is the host PHP version.
     */
    public function is_host_version(): bool
    {
        return $this->equals(self::get_host_version());
    }
    /**
     * Check whether this PHP version supports the given builtin type. Type name must be lowercase.
     */
    public function supports_builtin_type(string $type): bool
    {
        $min_version = self::BUILTIN_TYPE_VERSIONS[$type] ?? null;
        return $min_version !== null && $this->id >= $min_version;
    }
    /**
     * Whether this version supports [] array literals.
     */
    public function supports_short_array_syntax(): bool
    {
        return $this->id >= 50400;
    }
    /**
     * Whether this version supports [] for destructuring.
     */
    public function supports_short_array_destructuring(): bool
    {
        return $this->id >= 70100;
    }
    /**
     * Whether this version supports flexible heredoc/nowdoc.
     */
    public function supports_flexible_heredoc(): bool
    {
        return $this->id >= 70300;
    }
    /**
     * Whether this version supports trailing commas in parameter lists.
     */
    public function supports_trailing_comma_in_param_list(): bool
    {
        return $this->id >= 80000;
    }
    /**
     * Whether this version allows "$var =& new Obj".
     */
    public function allows_assign_new_by_reference(): bool
    {
        return $this->id < 70000;
    }
    /**
     * Whether this version allows invalid octals like "08".
     */
    public function allows_invalid_octals(): bool
    {
        return $this->id < 70000;
    }
    /**
     * Whether this version allows DEL (\x7f) to occur in identifiers.
     */
    public function allows_del_in_identifiers(): bool
    {
        return $this->id < 70100;
    }
    /**
     * Whether this version supports yield in expression context without parentheses.
     */
    public function supports_yield_without_parentheses(): bool
    {
        return $this->id >= 70000;
    }
    /**
     * Whether this version supports unicode escape sequences in strings.
     */
    public function supports_unicode_escapes(): bool
    {
        return $this->id >= 70000;
    }
    /*
     * Whether this version supports attributes.
     */
    public function supports_attributes(): bool
    {
        return $this->id >= 80000;
    }
    public function supports_new_dereference_without_parentheses(): bool
    {
        return $this->id >= 80400;
    }
}