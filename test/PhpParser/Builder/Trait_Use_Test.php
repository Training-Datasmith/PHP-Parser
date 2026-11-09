<?php

declare(strict_types=1);

namespace Php_Parser\Builder;

use Php_Parser\Node\Name;
use Php_Parser\Node\Stmt;

class Trait_Use_Test extends \PHPUnit\Framework\TestCase
{
    protected function createTraitUseBuilder(...$traits)
    {
        return new Trait_Use(...$traits);
    }

    public function testAnd(): void
    {
        $node = $this->createTraitUseBuilder('SomeTrait')
            ->and('AnotherTrait')
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Trait_Use([
                new Name('SomeTrait'),
                new Name('AnotherTrait'),
            ]),
            $node
        );
    }

    public function testWith(): void
    {
        $node = $this->createTraitUseBuilder('SomeTrait')
            ->with(new Stmt\Trait_Use_Adaptation\Alias(null, 'foo', null, 'bar'))
            ->with((new Trait_Use_Adaptation(null, 'test'))->as('baz'))
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Trait_Use([new Name('SomeTrait')], [
                new Stmt\Trait_Use_Adaptation\Alias(null, 'foo', null, 'bar'),
                new Stmt\Trait_Use_Adaptation\Alias(null, 'test', null, 'baz'),
            ]),
            $node
        );
    }

    public function testInvalidAdaptationNode(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Adaptation must have type Trait_Use_Adaptation');
        $this->createTraitUseBuilder('Test')
            ->with(new Stmt\Echo_([]))
        ;
    }
}
