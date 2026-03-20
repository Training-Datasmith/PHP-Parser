<?php

declare (strict_types=1);
namespace Php_Parser\Lexer\Token_Emulator;

use Php_Parser\Php_Version;
final class Match_Token_Emulator extends Keyword_Emulator
{
    public function get_php_version(): Php_Version
    {
        return Php_Version::from_components(8, 0);
    }
    public function get_keyword_string(): string
    {
        return 'match';
    }
    public function get_keyword_token(): int
    {
        return \T_MATCH;
    }
}