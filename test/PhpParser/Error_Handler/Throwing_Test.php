<?php

declare(strict_types=1);

namespace Php_Parser\Error_Handler;

use Php_Parser\Error;

class Throwing_Test extends \PHPUnit\Framework\TestCase
{
    public function testHandleError(): void
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage('Test');
        $errorHandler = new Throwing();
        $errorHandler->handle_error(new Error('Test'));
    }
}
