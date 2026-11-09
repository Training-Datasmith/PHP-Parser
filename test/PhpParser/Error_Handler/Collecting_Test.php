<?php

declare(strict_types=1);

namespace Php_Parser\Error_Handler;

use Php_Parser\Error;

class Collecting_Test extends \PHPUnit\Framework\TestCase
{
    public function testHandleError(): void
    {
        $errorHandler = new Collecting();
        $this->assertFalse($errorHandler->has_errors());
        $this->assertEmpty($errorHandler->get_errors());

        $errorHandler->handle_error($e1 = new Error('Test 1'));
        $errorHandler->handle_error($e2 = new Error('Test 2'));
        $this->assertTrue($errorHandler->has_errors());
        $this->assertSame([$e1, $e2], $errorHandler->get_errors());

        $errorHandler->clear_errors();
        $this->assertFalse($errorHandler->has_errors());
        $this->assertEmpty($errorHandler->get_errors());
    }
}
