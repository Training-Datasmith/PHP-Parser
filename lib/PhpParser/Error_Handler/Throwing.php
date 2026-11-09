<?php

declare (strict_types=1);
namespace Php_Parser\Error_Handler;

use Php_Parser\Error;
use Php_Parser\Error_Handler;
/**
 * Error handler that re-throws every parse error immediately.
 *
 * This is the default strategy used by the parser and lexer. When this handler
 * is active, the first syntax error in the source code causes an {@see Error}
 * exception to be thrown. No partial AST is returned.
 *
 * Use {@see Collecting} instead when you need error recovery (i.e. a partial
 * AST plus a list of errors).
 */
class Throwing implements Error_Handler
{
    /**
     * Throws the parse error immediately, aborting any ongoing parse.
     *
     * @param Error $error The parse error to throw
     *
     * @return never This method never returns normally — it always throws
     *
     * @throws Error always — every call to this method terminates with an exception
     */
    public function handle_error(Error $error): void
    {
        throw $error;
    }
}