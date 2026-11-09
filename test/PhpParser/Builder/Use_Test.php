<?php

declare(strict_types=1);

namespace Php_Parser\Builder;

use Php_Parser\Builder;
use Php_Parser\Node\Name;
use Php_Parser\Node\Stmt;

class Use_Test extends \PHPUnit\Framework\TestCase
{
    protected function createUseBuilder($name, $type = Stmt\Use_::TYPE_NORMAL)
    {
        return new Builder\Use_($name, $type);
    }

    public function testCreation(): void
    {
        $node = $this->createUseBuilder('Foo\Bar')->get_node();
        $this->assertEquals(new Stmt\Use_([
            new \Php_Parser\Node\Use_Item(new Name('Foo\Bar'), null),
        ]), $node);

        $node = $this->createUseBuilder(new Name('Foo\Bar'))->as('XYZ')->get_node();
        $this->assertEquals(new Stmt\Use_([
            new \Php_Parser\Node\Use_Item(new Name('Foo\Bar'), 'XYZ'),
        ]), $node);

        $node = $this->createUseBuilder('foo\bar', Stmt\Use_::TYPE_FUNCTION)->as('foo')->get_node();
        $this->assertEquals(new Stmt\Use_([
            new \Php_Parser\Node\Use_Item(new Name('foo\bar'), 'foo'),
        ], Stmt\Use_::TYPE_FUNCTION), $node);

        $node = $this->createUseBuilder('foo\BAR', Stmt\Use_::TYPE_CONSTANT)->as('FOO')->get_node();
        $this->assertEquals(new Stmt\Use_([
            new \Php_Parser\Node\Use_Item(new Name('foo\BAR'), 'FOO'),
        ], Stmt\Use_::TYPE_CONSTANT), $node);
    }
}
