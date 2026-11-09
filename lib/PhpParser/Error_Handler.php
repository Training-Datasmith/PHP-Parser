<?php

declare (strict_types=1);
namespace Php_Parser;

/**
 * Strategy interface for handling parse and lexer errors.
 *
 * Two built-in implementations are provided:
 *  - {@see ErrorHandler\Throwing} (default) — throws the error immediately
 *  - {@see ErrorHandler\Collecting} — accumulates errors for later inspection
 *
 * Implement this interface to create custom error handling strategies, such as
 * logging errors to a file or filtering out specific error types.
 */
interface Error_Handler
{
    /**
     * Handles an error generated during lexing, parsing, or another operation.
     *
     * Implementations may throw the error (like {@see ErrorHandler\Throwing}),
     * collect it (like {@see ErrorHandler\Collecting}), or silently discard it.
     *
     * @param Error $error The parse error that needs to be handled
     */
    public function handle_error(Error $error): void;
}