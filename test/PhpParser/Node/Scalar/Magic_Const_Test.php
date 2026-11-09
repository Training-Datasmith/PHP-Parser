<?php

declare(strict_types=1);

namespace Php_Parser\Node\Scalar;

class Magic_Const_Test extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provideTestGetName
     */
    public function testGetName(Magic_Const $magicConst, $name): void
    {
        $this->assertSame($name, $magicConst->get_name());
    }

    public static function provideTestGetName()
    {
        return [
            [new Magic_Const\Class_(), '__CLASS__'],
            [new Magic_Const\Dir(), '__DIR__'],
            [new Magic_Const\File(), '__FILE__'],
            [new Magic_Const\Function_(), '__FUNCTION__'],
            [new Magic_Const\Line(), '__LINE__'],
            [new Magic_Const\Method(), '__METHOD__'],
            [new Magic_Const\Namespace_(), '__NAMESPACE__'],
            [new Magic_Const\Trait_(), '__TRAIT__'],
        ];
    }
}
