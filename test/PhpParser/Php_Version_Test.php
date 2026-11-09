<?php

declare(strict_types=1);

namespace Php_Parser;

class Php_Version_Test extends \PHPUnit\Framework\TestCase
{
    public function testConst_ruction(): void
    {
        $version = Php_Version::from_components(8, 2);
        $this->assertSame(80200, $version->id);

        $version = Php_Version::from_string('8.2');
        $this->assertSame(80200, $version->id);

        $version = Php_Version::from_string('8.2.14');
        $this->assertSame(80200, $version->id);

        $version = Php_Version::from_string('8.2.14rc1');
        $this->assertSame(80200, $version->id);
    }

    public function testInvalidVersion(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid PHP version "8"');
        Php_Version::from_string('8');
    }

    public function testEquals(): void
    {
        $php74 = Php_Version::from_components(7, 4);
        $php81 = Php_Version::from_components(8, 1);
        $php82 = Php_Version::from_components(8, 2);
        $this->assertTrue($php81->equals($php81));
        $this->assertFalse($php81->equals($php82));

        $this->assertTrue($php81->older($php82));
        $this->assertFalse($php81->older($php81));
        $this->assertFalse($php81->older($php74));

        $this->assertFalse($php81->newer_or_equal($php82));
        $this->assertTrue($php81->newer_or_equal($php81));
        $this->assertTrue($php81->newer_or_equal($php74));
    }
}
