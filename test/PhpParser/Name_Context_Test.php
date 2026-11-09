<?php

declare(strict_types=1);

namespace Php_Parser;

use Php_Parser\Node\Name;
use Php_Parser\Node\Stmt\Use_;

class Name_Context_Test extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provideTestGetPossibleNames
     */
    public function testGetPossibleNames($type, $name, $expectedPossibleNames): void
    {
        $nameContext = new Name_Context(new Error_Handler\Throwing());
        $nameContext->start_namespace(new Name('NS'));
        $nameContext->add_alias(new Name('Foo'), 'Foo', Use_::TYPE_NORMAL);
        $nameContext->add_alias(new Name('Foo\Bar'), 'Alias', Use_::TYPE_NORMAL);
        $nameContext->add_alias(new Name('Foo\fn'), 'fn', Use_::TYPE_FUNCTION);
        $nameContext->add_alias(new Name('Foo\CN'), 'CN', Use_::TYPE_CONSTANT);

        $possibleNames = $nameContext->get_possible_names($name, $type);
        $possibleNames = array_map(function (Name $name) {
            return $name->to_code_string();
        }, $possibleNames);

        $this->assertSame($expectedPossibleNames, $possibleNames);

        // Here the last name is always the shortest one
        $expectedShortName = $expectedPossibleNames[count($expectedPossibleNames) - 1];
        $this->assertSame(
            $expectedShortName,
            $nameContext->get_short_name($name, $type)->to_code_string()
        );
    }

    public static function provideTestGetPossibleNames()
    {
        return [
            [Use_::TYPE_NORMAL, 'Test', ['\Test']],
            [Use_::TYPE_NORMAL, 'Test\Namespaced', ['\Test\Namespaced']],
            [Use_::TYPE_NORMAL, 'NS\Test', ['\NS\Test', 'Test']],
            [Use_::TYPE_NORMAL, 'ns\Test', ['\ns\Test', 'Test']],
            [Use_::TYPE_NORMAL, 'NS\Foo\Bar', ['\NS\Foo\Bar']],
            [Use_::TYPE_NORMAL, 'ns\foo\Bar', ['\ns\foo\Bar']],
            [Use_::TYPE_NORMAL, 'Foo', ['\Foo', 'Foo']],
            [Use_::TYPE_NORMAL, 'Foo\Bar', ['\Foo\Bar', 'Foo\Bar', 'Alias']],
            [Use_::TYPE_NORMAL, 'Foo\Bar\Baz', ['\Foo\Bar\Baz', 'Foo\Bar\Baz', 'Alias\Baz']],
            [Use_::TYPE_NORMAL, 'Foo\fn\Bar', ['\Foo\fn\Bar', 'Foo\fn\Bar']],
            [Use_::TYPE_FUNCTION, 'Foo\fn\bar', ['\Foo\fn\bar', 'Foo\fn\bar']],
            [Use_::TYPE_FUNCTION, 'Foo\fn', ['\Foo\fn', 'Foo\fn', 'fn']],
            [Use_::TYPE_FUNCTION, 'Foo\FN', ['\Foo\FN', 'Foo\FN', 'fn']],
            [Use_::TYPE_CONSTANT, 'Foo\CN\BAR', ['\Foo\CN\BAR', 'Foo\CN\BAR']],
            [Use_::TYPE_CONSTANT, 'Foo\CN', ['\Foo\CN', 'Foo\CN', 'CN']],
            [Use_::TYPE_CONSTANT, 'foo\CN', ['\foo\CN', 'Foo\CN', 'CN']],
            [Use_::TYPE_CONSTANT, 'foo\cn', ['\foo\cn', 'Foo\cn']],
            // self/parent/static must not be fully qualified
            [Use_::TYPE_NORMAL, 'self', ['self']],
            [Use_::TYPE_NORMAL, 'parent', ['parent']],
            [Use_::TYPE_NORMAL, 'static', ['static']],
            // true/false/null do not need to be fully qualified, even in namespaces
            [Use_::TYPE_CONSTANT, 'true', ['\true', 'true']],
            [Use_::TYPE_CONSTANT, 'false', ['\false', 'false']],
            [Use_::TYPE_CONSTANT, 'null', ['\null', 'null']],
        ];
    }
}
