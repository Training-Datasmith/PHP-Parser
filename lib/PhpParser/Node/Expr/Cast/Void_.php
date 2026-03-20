<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr\Cast;

use Php_Parser\Node\Expr\Cast;
class Void_ extends Cast
{
    public function get_type(): string
    {
        return 'Expr_Cast_Void';
    }
}