<?php

declare (strict_types=1);
namespace Php_Parser;

interface Error_Handler
{
    /**
     * Handle an error generated during lexing, parsing or some other operation.
     *
     * @param Error $error The error that needs to be handled
     */
    public function handle_error(Error $error): void;
}