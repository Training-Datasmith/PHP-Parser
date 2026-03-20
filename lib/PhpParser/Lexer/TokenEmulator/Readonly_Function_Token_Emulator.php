<?php

declare (strict_types=1);
namespace Php_Parser\Lexer\Token_Emulator;

use Php_Parser\Php_Version;
/*
 * In PHP 8.1, "readonly(" was special cased in the lexer in order to support functions with
 * name readonly. In PHP 8.2, this may conflict with readonly properties having a DNF type. For
 * this reason, PHP 8.2 instead treats this as T_READONLY and then handles it specially in the
 * parser. This emulator only exists to handle this special case, which is skipped by the
 * PHP 8.1 ReadonlyTokenEmulator.
 */
class Readonly_Function_Token_Emulator extends Keyword_Emulator
{
    public function get_keyword_string(): string
    {
        return 'readonly';
    }
    public function get_keyword_token(): int
    {
        return \T_READONLY;
    }
    public function get_php_version(): Php_Version
    {
        return Php_Version::from_components(8, 2);
    }
    public function reverse_emulate(string $code, array $tokens): array
    {
        // Don't bother
        return $tokens;
    }
}