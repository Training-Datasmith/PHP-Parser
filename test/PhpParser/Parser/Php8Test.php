<?php

declare(strict_types=1);

namespace Php_Parser\Parser;

use Php_Parser\Lexer;
use Php_Parser\Parser_Test_Abstract;

class Php8Test extends Parser_Test_Abstract
{
    protected function getParser(Lexer $lexer)
    {
        return new Php8($lexer);
    }
}
