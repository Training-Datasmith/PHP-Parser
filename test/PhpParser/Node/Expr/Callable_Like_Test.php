<?php

declare(strict_types=1);

namespace Php_Parser\Node\Expr;

use Php_Parser\Node\Arg;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Variadic_Placeholder;

class Callable_Like_Test extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provideTestIsFirstClassCallable
     */
    public function testIsFirstClassCallable(Call_Like $node, bool $isFirstClassCallable): void
    {
        $this->assertSame($isFirstClassCallable, $node->is_first_class_callable());
        if (!$isFirstClassCallable) {
            $this->assertSame($node->get_raw_args(), $node->get_args());
        }
    }

    /**
     * @dataProvider provideTestGetArg
     */
    public function testGetArg(Call_Like $node, ?Arg $expected): void
    {
        $this->assertSame($expected, $node->get_arg('bar', 1));
    }

    public static function provideTestIsFirstClassCallable()
    {
        $normalArgs = [new Arg(new Int_(1))];
        $callableArgs = [new Variadic_Placeholder()];
        return [
            [new Func_Call(new Name('test'), $normalArgs), false],
            [new Func_Call(new Name('test'), $callableArgs), true],
            [new Method_Call(new Variable('this'), 'test', $normalArgs), false],
            [new Method_Call(new Variable('this'), 'test', $callableArgs), true],
            [new Static_Call(new Name('Test'), 'test', $normalArgs), false],
            [new Static_Call(new Name('Test'), 'test', $callableArgs), true],
            [new New_(new Name('Test'), $normalArgs), false],
            [new Nullsafe_Method_Call(new Variable('this'), 'test', $normalArgs), false],
            // This is not legal code, but accepted by the parser.
            [new New_(new Name('Test'), $callableArgs), true],
            [new Nullsafe_Method_Call(new Variable('this'), 'test', $callableArgs), true],
        ];
    }

    public static function provideTestGetArg()
    {
        $foo = new Arg(new Int_(1));
        $namedFoo = new Arg(new Int_(1), false, false, [], new Identifier('foo'));
        $bar = new Arg(new Int_(2));
        $namedBar = new Arg(new Int_(2), false, false, [], new Identifier('bar'));
        $unpack = new Arg(new Int_(3), false, true);
        $callableArgs = [new Variadic_Placeholder()];
        return [
            [new Func_Call(new Name('test'), [$foo]), null],
            [new Func_Call(new Name('test'), [$namedFoo]), null],
            [new Func_Call(new Name('test'), [$foo, $bar]), $bar],
            [new Func_Call(new Name('test'), [$namedBar]), $namedBar],
            [new Func_Call(new Name('test'), [$namedFoo, $namedBar]), $namedBar],
            [new Func_Call(new Name('test'), [$namedBar, $namedFoo]), $namedBar],
            [new Func_Call(new Name('test'), [$namedFoo, $unpack]), null],
            [new Func_Call(new Name('test'), $callableArgs), null],
            [new Method_Call(new Variable('this'), 'test', [$foo]), null],
            [new Method_Call(new Variable('this'), 'test', [$namedFoo]), null],
            [new Method_Call(new Variable('this'), 'test', [$foo, $bar]), $bar],
            [new Method_Call(new Variable('this'), 'test', [$namedBar]), $namedBar],
            [new Method_Call(new Variable('this'), 'test', [$namedFoo, $namedBar]), $namedBar],
            [new Method_Call(new Variable('this'), 'test', [$namedBar, $namedFoo]), $namedBar],
            [new Method_Call(new Variable('this'), 'test', [$namedFoo, $unpack]), null],
            [new Method_Call(new Variable('this'), 'test', $callableArgs), null],
            [new Static_Call(new Name('Test'), 'test', [$foo]), null],
            [new Static_Call(new Name('Test'), 'test', [$namedFoo]), null],
            [new Static_Call(new Name('Test'), 'test', [$foo, $bar]), $bar],
            [new Static_Call(new Name('Test'), 'test', [$namedBar]), $namedBar],
            [new Static_Call(new Name('Test'), 'test', [$namedFoo, $namedBar]), $namedBar],
            [new Static_Call(new Name('Test'), 'test', [$namedBar, $namedFoo]), $namedBar],
            [new Static_Call(new Name('Test'), 'test', [$namedFoo, $unpack]), null],
            [new Static_Call(new Name('Test'), 'test', $callableArgs), null],
            [new New_(new Name('test'), [$foo]), null],
            [new New_(new Name('test'), [$namedFoo]), null],
            [new New_(new Name('test'), [$foo, $bar]), $bar],
            [new New_(new Name('test'), [$namedBar]), $namedBar],
            [new New_(new Name('test'), [$namedFoo, $namedBar]), $namedBar],
            [new New_(new Name('test'), [$namedBar, $namedFoo]), $namedBar],
            [new New_(new Name('test'), [$namedFoo, $unpack]), null],
            [new Nullsafe_Method_Call(new Variable('this'), 'test', [$foo]), null],
            [new Nullsafe_Method_Call(new Variable('this'), 'test', [$namedFoo]), null],
            [new Nullsafe_Method_Call(new Variable('this'), 'test', [$foo, $bar]), $bar],
            [new Nullsafe_Method_Call(new Variable('this'), 'test', [$namedBar]), $namedBar],
            [new Nullsafe_Method_Call(new Variable('this'), 'test', [$namedFoo, $namedBar]), $namedBar],
            [new Nullsafe_Method_Call(new Variable('this'), 'test', [$namedBar, $namedFoo]), $namedBar],
            [new Nullsafe_Method_Call(new Variable('this'), 'test', [$namedFoo, $unpack]), null],
            // This is not legal code, but accepted by the parser.
            [new New_(new Name('Test'), $callableArgs), null],
            [new Nullsafe_Method_Call(new Variable('this'), 'test', $callableArgs), null],
        ];
    }
}
