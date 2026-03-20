<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr;

use Php_Parser\Node\Arg;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Variadic_Placeholder;
abstract class Call_Like extends Expr
{
    /**
     * Return raw arguments, which may be actual Args, or VariadicPlaceholders for first-class
     * callables.
     *
     * @return array<Arg|VariadicPlaceholder>
     */
    abstract public function get_raw_args(): array;
    /**
     * Returns whether this call expression is actually a first class callable.
     */
    public function is_first_class_callable(): bool
    {
        $raw_args = $this->get_raw_args();
        return count($raw_args) === 1 && current($raw_args) instanceof Variadic_Placeholder;
    }
    /**
     * Assert that this is not a first-class callable and return only ordinary Args.
     *
     * @return Arg[]
     */
    public function get_args(): array
    {
        assert(!$this->is_first_class_callable());
        return $this->get_raw_args();
    }
    /**
     * Retrieves a specific argument from the raw arguments.
     *
     * Returns the named argument that matches the given `$name`, or the
     * positional (unnamed) argument that exists at the given `$position`,
     * otherwise, returns `null` for first-class callables or if no match is found.
     */
    public function get_arg(string $name, int $position): ?Arg
    {
        if ($this->is_first_class_callable()) {
            return null;
        }
        foreach ($this->get_raw_args() as $i => $arg) {
            if ($arg->unpack) {
                continue;
            }
            if ($arg->name !== null && $arg->name->to_string() === $name || $arg->name === null && $i === $position) {
                return $arg;
            }
        }
        return null;
    }
}