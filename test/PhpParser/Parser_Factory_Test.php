<?php

declare(strict_types=1);

namespace Php_Parser;

/* This test is very weak, because PHPUnit's assertEquals assertion is way too slow dealing with the
 * large objects involved here. So we just do some basic instanceof tests instead. */

use Php_Parser\Parser\Php8;

class Parser_Factory_Test extends \PHPUnit\Framework\TestCase
{
    public function testCreate(): void
    {
        $factory = new Parser_Factory();
        $this->assertInstanceOf(Php8::class, $factory->create_for_newest_supported_version());
        $this->assertInstanceOf(Parser::class, $factory->create_for_host_version());
    }
}
