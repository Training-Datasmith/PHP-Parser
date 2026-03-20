<?php

declare (strict_types=1);
namespace Php_Parser;

use Php_Parser\Node\Name;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Parser\Node\Stmt;
class Name_Context
{
    /** @var null|Name Current namespace */
    protected ?Name $namespace = null;
    /** @var Name[][] Map of format [aliasType => [aliasName => originalName]] */
    protected array $aliases = [];
    /** @var Name[][] Same as $aliases but preserving original case */
    protected array $orig_aliases = [];
    /** @var ErrorHandler Error handler */
    protected Error_Handler $error_handler;
    /**
     * Create a name context.
     *
     * @param ErrorHandler $errorHandler Error handling used to report errors
     */
    public function __construct(Error_Handler $error_handler)
    {
        $this->error_handler = $error_handler;
    }
    /**
     * Start a new namespace.
     *
     * This also resets the alias table.
     *
     * @param Name|null $namespace Null is the global namespace
     */
    public function start_namespace(?Name $namespace = null): void
    {
        $this->namespace = $namespace;
        $this->orig_aliases = $this->aliases = [Stmt\Use_::TYPE_NORMAL => [], Stmt\Use_::TYPE_FUNCTION => [], Stmt\Use_::TYPE_CONSTANT => []];
    }
    /**
     * Add an alias / import.
     *
     * @param Name $name Original name
     * @param string $aliasName Aliased name
     * @param Stmt\Use_::TYPE_* $type One of Stmt\Use_::TYPE_*
     * @param array<string, mixed> $errorAttrs Attributes to use to report an error
     */
    public function add_alias(Name $name, string $alias_name, int $type, array $error_attrs = []): void
    {
        // Constant names are case sensitive, everything else case insensitive
        if ($type === Stmt\Use_::TYPE_CONSTANT) {
            $alias_lookup_name = $alias_name;
        } else {
            $alias_lookup_name = strtolower($alias_name);
        }
        if (isset($this->aliases[$type][$alias_lookup_name])) {
            $type_string_map = [Stmt\Use_::TYPE_NORMAL => '', Stmt\Use_::TYPE_FUNCTION => 'function ', Stmt\Use_::TYPE_CONSTANT => 'const '];
            $this->error_handler->handle_error(new Error(sprintf('Cannot use %s%s as %s because the name is already in use', $type_string_map[$type], $name, $alias_name), $error_attrs));
            return;
        }
        $this->aliases[$type][$alias_lookup_name] = $name;
        $this->orig_aliases[$type][$alias_name] = $name;
    }
    /**
     * Get current namespace.
     *
     * @return null|Name Namespace (or null if global namespace)
     */
    public function get_namespace(): ?Name
    {
        return $this->namespace;
    }
    /**
     * Get resolved name.
     *
     * @param Name $name Name to resolve
     * @param Stmt\Use_::TYPE_* $type One of Stmt\Use_::TYPE_{FUNCTION|CONSTANT}
     *
     * @return null|Name Resolved name, or null if static resolution is not possible
     */
    public function get_resolved_name(Name $name, int $type): ?Name
    {
        // don't resolve special class names
        if ($type === Stmt\Use_::TYPE_NORMAL && $name->is_special_class_name()) {
            if (!$name->is_unqualified()) {
                $this->error_handler->handle_error(new Error(sprintf("'\\%s' is an invalid class name", $name->to_string()), $name->get_attributes()));
            }
            return $name;
        }
        // fully qualified names are already resolved
        if ($name->is_fully_qualified()) {
            return $name;
        }
        // Try to resolve aliases
        if (null !== $resolved_name = $this->resolve_alias($name, $type)) {
            return $resolved_name;
        }
        if ($type !== Stmt\Use_::TYPE_NORMAL && $name->is_unqualified()) {
            if (null === $this->namespace) {
                // outside of a namespace unaliased unqualified is same as fully qualified
                return new Fully_Qualified($name, $name->get_attributes());
            }
            // Cannot resolve statically
            return null;
        }
        // if no alias exists prepend current namespace
        return Fully_Qualified::concat($this->namespace, $name, $name->get_attributes());
    }
    /**
     * Get resolved class name.
     *
     * @param Name $name Class ame to resolve
     *
     * @return Name Resolved name
     */
    public function get_resolved_class_name(Name $name): Name
    {
        return $this->get_resolved_name($name, Stmt\Use_::TYPE_NORMAL);
    }
    /**
     * Get possible ways of writing a fully qualified name (e.g., by making use of aliases).
     *
     * @param string $name Fully-qualified name (without leading namespace separator)
     * @param Stmt\Use_::TYPE_* $type One of Stmt\Use_::TYPE_*
     *
     * @return Name[] Possible representations of the name
     */
    public function get_possible_names(string $name, int $type): array
    {
        $lc_name = strtolower($name);
        if ($type === Stmt\Use_::TYPE_NORMAL) {
            // self, parent and static must always be unqualified
            if ($lc_name === 'self' || $lc_name === 'parent' || $lc_name === 'static') {
                return [new Name($name)];
            }
        }
        // Collect possible ways to write this name, starting with the fully-qualified name
        $possible_names = [new Fully_Qualified($name)];
        if (null !== $ns_relative_name = $this->get_namespace_relative_name($name, $lc_name, $type)) {
            // Make sure there is no alias that makes the normally namespace-relative name
            // into something else
            if (null === $this->resolve_alias($ns_relative_name, $type)) {
                $possible_names[] = $ns_relative_name;
            }
        }
        // Check for relevant namespace use statements
        foreach ($this->orig_aliases[Stmt\Use_::TYPE_NORMAL] as $alias => $orig) {
            $lc_orig = $orig->to_lower_string();
            if (0 === strpos($lc_name, $lc_orig . '\\')) {
                $possible_names[] = new Name($alias . substr($name, strlen($lc_orig)));
            }
        }
        // Check for relevant type-specific use statements
        foreach ($this->orig_aliases[$type] as $alias => $orig) {
            if ($type === Stmt\Use_::TYPE_CONSTANT) {
                // Constants are complicated-sensitive
                $normalized_orig = $this->normalize_const_name($orig->to_string());
                if ($normalized_orig === $this->normalize_const_name($name)) {
                    $possible_names[] = new Name($alias);
                }
            } else if ($orig->to_lower_string() === $lc_name) {
                $possible_names[] = new Name($alias);
            }
        }
        return $possible_names;
    }
    /**
     * Get shortest representation of this fully-qualified name.
     *
     * @param string $name Fully-qualified name (without leading namespace separator)
     * @param Stmt\Use_::TYPE_* $type One of Stmt\Use_::TYPE_*
     *
     * @return Name Shortest representation
     */
    public function get_short_name(string $name, int $type): Name
    {
        $possible_names = $this->get_possible_names($name, $type);
        // Find shortest name
        $shortest_name = null;
        $shortest_length = \INF;
        foreach ($possible_names as $possible_name) {
            $length = strlen($possible_name->to_code_string());
            if ($length < $shortest_length) {
                $shortest_name = $possible_name;
                $shortest_length = $length;
            }
        }
        return $shortest_name;
    }
    private function resolve_alias(Name $name, int $type): ?Fully_Qualified
    {
        $first_part = $name->get_first();
        if ($name->is_qualified()) {
            // resolve aliases for qualified names, always against class alias table
            $check_name = strtolower($first_part);
            if (isset($this->aliases[Stmt\Use_::TYPE_NORMAL][$check_name])) {
                $alias = $this->aliases[Stmt\Use_::TYPE_NORMAL][$check_name];
                return Fully_Qualified::concat($alias, $name->slice(1), $name->get_attributes());
            }
        } elseif ($name->is_unqualified()) {
            // constant aliases are case-sensitive, function aliases case-insensitive
            $check_name = $type === Stmt\Use_::TYPE_CONSTANT ? $first_part : strtolower($first_part);
            if (isset($this->aliases[$type][$check_name])) {
                // resolve unqualified aliases
                return new Fully_Qualified($this->aliases[$type][$check_name], $name->get_attributes());
            }
        }
        // No applicable aliases
        return null;
    }
    private function get_namespace_relative_name(string $name, string $lc_name, int $type): ?Name
    {
        if (null === $this->namespace) {
            return new Name($name);
        }
        if ($type === Stmt\Use_::TYPE_CONSTANT) {
            // The constants true/false/null always resolve to the global symbols, even inside a
            // namespace, so they may be used without qualification
            if ($lc_name === 'true' || $lc_name === 'false' || $lc_name === 'null') {
                return new Name($name);
            }
        }
        $namespace_prefix = strtolower($this->namespace . '\\');
        if (0 === strpos($lc_name, $namespace_prefix)) {
            return new Name(substr($name, strlen($namespace_prefix)));
        }
        return null;
    }
    private function normalize_const_name(string $name): string
    {
        $ns_sep = strrpos($name, '\\');
        if (false === $ns_sep) {
            return $name;
        }
        // Constants have case-insensitive namespace and case-sensitive short-name
        $ns = substr($name, 0, $ns_sep);
        $short_name = substr($name, $ns_sep + 1);
        return strtolower($ns) . '\\' . $short_name;
    }
}