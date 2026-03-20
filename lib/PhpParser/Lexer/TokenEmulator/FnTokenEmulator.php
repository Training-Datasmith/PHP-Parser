<?php

declare (strict_types=1);
namespace Php_Parser\Lexer\Token_Emulator;

use Php_Parser\Php_Version;
// Retained for reverse emulation support only.
final class Fn_Token_Emulator extends Keyword_Emulator
{
    public function get_php_version(): Php_Version
    {
        return Php_Version::from_string('7.4');
    }
    public function get_keyword_string(): string
    {
        return 'fn';
    }
    public function get_keyword_token(): int
    {
        return \T_FN;
    }
}