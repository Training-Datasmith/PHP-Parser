<?php

declare (strict_types=1);
namespace Php_Parser\Lexer\Token_Emulator;

use Php_Parser\Php_Version;
use Php_Parser\Token;
class Explicit_Octal_Emulator extends Token_Emulator
{
    public function get_php_version(): Php_Version
    {
        return Php_Version::from_components(8, 1);
    }
    public function is_emulation_needed(string $code): bool
    {
        return strpos($code, '0o') !== false || strpos($code, '0O') !== false;
    }
    public function emulate(string $code, array $tokens): array
    {
        for ($i = 0, $c = count($tokens); $i < $c; ++$i) {
            $token = $tokens[$i];
            if ($token->id == \T_LNUMBER && $token->text === '0' && isset($tokens[$i + 1]) && $tokens[$i + 1]->id == \T_STRING && preg_match('/[oO][0-7]+(?:_[0-7]+)*/', $tokens[$i + 1]->text)) {
                $token_kind = $this->resolve_integer_or_float_token($tokens[$i + 1]->text);
                array_splice($tokens, $i, 2, [new Token($token_kind, '0' . $tokens[$i + 1]->text, $token->line, $token->pos)]);
                $c--;
            }
        }
        return $tokens;
    }
    private function resolve_integer_or_float_token(string $str): int
    {
        $str = substr($str, 1);
        $str = str_replace('_', '', $str);
        $num = octdec($str);
        return is_float($num) ? \T_DNUMBER : \T_LNUMBER;
    }
    public function reverse_emulate(string $code, array $tokens): array
    {
        // Explicit octals were not legal code previously, don't bother.
        return $tokens;
    }
}