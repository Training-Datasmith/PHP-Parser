<?php

declare(strict_types=1);

namespace Php_Parser\Builder;

use Php_Parser\Modifiers;
use Php_Parser\Node\Name;
use Php_Parser\Node\Stmt;

class Trait_Use_Adaptation_Test extends \PHPUnit\Framework\TestCase
{
    protected function createTrait_Use_AdaptationBuilder($trait, $method)
    {
        return new Trait_Use_Adaptation($trait, $method);
    }

    public function testAsMake(): void
    {
        $builder = $this->createTrait_Use_AdaptationBuilder(null, 'foo');

        $this->assertEquals(
            new Stmt\Trait_Use_Adaptation\Alias(null, 'foo', null, 'bar'),
            (clone $builder)->as('bar')->get_node()
        );

        $this->assertEquals(
            new Stmt\Trait_Use_Adaptation\Alias(null, 'foo', Modifiers::PUBLIC, null),
            (clone $builder)->make_public()->get_node()
        );

        $this->assertEquals(
            new Stmt\Trait_Use_Adaptation\Alias(null, 'foo', Modifiers::PROTECTED, null),
            (clone $builder)->make_protected()->get_node()
        );

        $this->assertEquals(
            new Stmt\Trait_Use_Adaptation\Alias(null, 'foo', Modifiers::PRIVATE, null),
            (clone $builder)->make_private()->get_node()
        );
    }

    public function testInsteadof(): void
    {
        $node = $this->createTrait_Use_AdaptationBuilder('SomeTrait', 'foo')
            ->insteadof('AnotherTrait')
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Trait_Use_Adaptation\Precedence(
                new Name('SomeTrait'),
                'foo',
                [new Name('AnotherTrait')]
            ),
            $node
        );
    }

    public function testAsOnNotAlias(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot set alias for not alias adaptation buider');
        $this->createTrait_Use_AdaptationBuilder('Test', 'foo')
            ->insteadof('AnotherTrait')
            ->as('bar')
        ;
    }

    public function testInsteadofOnNotPrecedence(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot add overwritten traits for not precedence adaptation buider');
        $this->createTrait_Use_AdaptationBuilder('Test', 'foo')
            ->as('bar')
            ->insteadof('AnotherTrait')
        ;
    }

    public function testInsteadofWithoutTrait(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Precedence adaptation must have trait');
        $this->createTrait_Use_AdaptationBuilder(null, 'foo')
            ->insteadof('AnotherTrait')
        ;
    }

    public function testMakeOnNotAlias(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot set access modifier for not alias adaptation buider');
        $this->createTrait_Use_AdaptationBuilder('Test', 'foo')
            ->insteadof('AnotherTrait')
            ->make_public()
        ;
    }

    public function testMultipleMake(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Multiple access type modifiers are not allowed');
        $this->createTrait_Use_AdaptationBuilder(null, 'foo')
            ->make_private()
            ->make_public()
        ;
    }

    public function testUndefinedType(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Type of adaptation is not defined');
        $this->createTrait_Use_AdaptationBuilder(null, 'foo')
            ->get_node()
        ;
    }
}
