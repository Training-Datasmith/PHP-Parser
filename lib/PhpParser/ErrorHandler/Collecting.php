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
    public function handle_error(Error $error): void
    {
        $this->errors[] = $error;
    }
    /**
     * Get collected errors.
     *
     * @return Error[]
     */
    public function get_errors(): array
    {
        return $this->errors;
    }
    /**
     * Check whether there are any errors.
     */
    public function has_errors(): bool
    {
        return !empty($this->errors);
    }
    /**
     * Reset/clear collected errors.
     */
    public function clear_errors(): void
    {
        $this->errors = [];
    }
}