<?php

declare (strict_types=1);
namespace Php_Parser\Lexer\Token_Emulator;

use Php_Parser\Php_Version;
final class Readonly_Token_Emulator extends Keyword_Emulator
{
    public function get_php_version(): Php_Version
    {
        return Php_Version::from_components(8, 1);
    }
    public function get_keyword_string(): string
    {
        return 'readonly';
    }
    public function get_keyword_token(): int
    {
        return \T_READONLY;
    }
    protected function is_keyword_context(array $tokens, int $pos): bool
    {
        if (!parent::is_keyword_context($tokens, $pos)) {
            return false;
        }
        // Support "function readonly("
        return !(isset($tokens[$pos + 1]) && ($tokens[$pos + 1]->text === '(' || $tokens[$pos + 1]->id === \T_WHITESPACE && isset($tokens[$pos + 2]) && $tokens[$pos + 2]->text === '('));
    }
}