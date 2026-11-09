<?php

declare(strict_types=1);

namespace Php_Parser;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ModifiersTest extends TestCase
{
    public function testToString()
    {
        $this->assertSame('public', Modifiers::to_string(Modifiers::PUBLIC));
    }

    public function testToStringInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown modifier 3');
        Modifiers::to_string(Modifiers::PUBLIC | Modifiers::PROTECTED);
    }
}
