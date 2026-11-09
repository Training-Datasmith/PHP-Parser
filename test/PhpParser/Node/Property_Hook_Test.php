<?php

declare(strict_types=1);

namespace Php_Parser\Node;

use Php_Parser\Modifiers;
use Php_Parser\Node\Expr\Assign;
use Php_Parser\Node\Expr\Property_Fetch;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Stmt\Expression;
use Php_Parser\Node\Stmt\Return_;
use Php_Parser\Parser_Factory;
use Php_Parser\Pretty_Printer\Standard;

class Property_Hook_Test extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provideModifiers
     */
    public function testModifiers($modifier): void
    {
        $node = new Property_Hook(
            'get',
            null,
            [
                'flags' => constant(Modifiers::class . '::' . strtoupper($modifier)),
            ]
        );

        $this->assertTrue($node->{'is_' . $modifier}());
    }

    public function testNoModifiers(): void
    {
        $node = new Property_Hook('get', null);

        $this->assertFalse($node->is_final());
    }

    public static function provideModifiers()
    {
        return [
            ['final'],
        ];
    }

    public function testGetStmts(): void
    {
        $expr = new Variable('test');
        $get = new Property_Hook('get', $expr);
        $this->assertEquals([new Return_($expr)], $get->get_stmts());

        $set = new Property_Hook('set', $expr, [], ['propertyName' => 'abc']);
        $this->assertEquals([
            new Expression(new Assign(new Property_Fetch(new Variable('this'), 'abc'), $expr)),
        ], $set->get_stmts());
    }

    public function testGetStmtsSetHookFromParser(): void
    {
        $parser = (new Parser_Factory())->create_for_newest_supported_version();
        $prettyPrinter = new Standard();
        $stmts = $parser->parse(<<<'CODE'
        <?php
        class Test {
            public $prop1 { set => 123; }

            public function __construct(public $prop2 { set => 456; }) {}
        }
        CODE);

        $hook1 = $stmts[0]->stmts[0]->hooks[0];
        $this->assertEquals('$this->prop1 = 123;', $prettyPrinter->pretty_print($hook1->get_stmts()));

        $hook2 = $stmts[0]->stmts[1]->params[0]->hooks[0];
        $this->assertEquals('$this->prop2 = 456;', $prettyPrinter->pretty_print($hook2->get_stmts()));
    }

    public function testGetStmtsUnknownHook(): void
    {
        $expr = new Variable('test');
        $hook = new Property_Hook('foobar', $expr);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unknown property hook "foobar"');
        $hook->get_stmts();
    }

    public function testGetStmtsSetHookWithoutPropertyName(): void
    {
        $expr = new Variable('test');
        $set = new Property_Hook('set', $expr);
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can only use getStmts() on a "set" hook if the "propertyName" attribute is set');
        $set->get_stmts();
    }
}
