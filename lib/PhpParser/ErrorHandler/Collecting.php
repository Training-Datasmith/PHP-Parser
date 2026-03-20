<?php

declare (strict_types=1);
namespace Php_Parser\Error_Handler;

use Php_Parser\Error;
use Php_Parser\Error_Handler;
/**
 * Error handler that collects all errors into an array.
 *
 * This allows graceful handling of errors.
 */
class Collecting implements Error_Handler
{
    /** @var Error[] Collected errors */
    private array $errors = [];
    /**
     * Appends the error to the internal collection instead of throwing it.
     *
     * This allows parsing to continue past errors, producing a partial AST.
     *
     * @param Error $error The parse error to collect
     */
    public function handle_error(Error $error): void
    {
        $this->errors[] = $error;
    }
    /**
     * Returns all parse errors collected so far.
     *
     * Call this after {@see Parser::parse()} to inspect errors that were
     * encountered but not thrown.
     *
     * @return Error[] List of collected parse errors in the order they were encountered
     */
    public function get_errors(): array
    {
        return $this->errors;
    }
    /**
     * Returns whether any parse errors have been collected.
     *
     * @return bool True if at least one error was collected, false otherwise
     */
    public function has_errors(): bool
    {
        return !empty($this->errors);
    }
    /**
     * Clears all previously collected errors.
     *
     * Call this before re-using the same handler instance to parse another file.
     */
    public function clear_errors(): void
    {
        $this->errors = [];
    }
}