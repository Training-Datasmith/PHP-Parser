<?php

declare (strict_types=1);
namespace Php_Parser\Error_Handler;

use Php_Parser\Error;
use Php_Parser\Error_Handler;
/**
 * Error handler that handles all errors by throwing them.
 *
 * This is the default strategy used by all components.
 */
class Throwing implements Error_Handler
{
    public function handle_error(Error $error): void
    {
        throw $error;
    }
}