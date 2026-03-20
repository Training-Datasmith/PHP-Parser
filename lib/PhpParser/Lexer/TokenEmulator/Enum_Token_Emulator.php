<?php

declare (strict_types=1);
namespace Php_Parser\Lexer\Token_Emulator;

use Php_Parser\Php_Version;
final class Enum_Token_Emulator extends Keyword_Emulator
{
    public function get_php_version(): Php_Version
    {
        return Php_Version::from_components(8, 1);
    }
    public function get_keyword_string(): string
    {
        return 'enum';
    }
    public function get_keyword_token(): int
    {
        return \T_ENUM;
    }
    protected function is_keyword_context(array $tokens, int $pos): bool
    {
        return parent::is_keyword_context($tokens, $pos) && isset($tokens[$pos + 2]) && $tokens[$pos + 1]->id === \T_WHITESPACE && $tokens[$pos + 2]->id === \T_STRING;
    }
}