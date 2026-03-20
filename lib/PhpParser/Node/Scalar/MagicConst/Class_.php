<?php

declare (strict_types=1);
namespace Php_Parser\Node\Scalar\Magic_Const;

use Php_Parser\Node\Scalar\Magic_Const;
class Class_ extends Magic_Const
{
    public function get_name(): string
    {
        return '__CLASS__';
    }
    public function get_type(): string
    {
        return 'Scalar_MagicConst_Class';
    }
}