<?php

declare (strict_types=1);
namespace Php_Parser\Lexer\Token_Emulator;

use Php_Parser\Php_Version;
use Php_Parser\Token;
final class Asymmetric_Visibility_Token_Emulator extends Token_Emulator
{
    public function get_php_version(): Php_Version
    {
        return Php_Version::from_components(8, 4);
    }
    public function is_emulation_needed(string $code): bool
    {
        $code = strtolower($code);
        return strpos($code, 'public(set)') !== false || strpos($code, 'protected(set)') !== false || strpos($code, 'private(set)') !== false;
    }
    public function emulate(string $code, array $tokens): array
    {
        $map = [\T_PUBLIC => \T_PUBLIC_SET, \T_PROTECTED => \T_PROTECTED_SET, \T_PRIVATE => \T_PRIVATE_SET];
        for ($i = 0, $c = count($tokens); $i < $c; ++$i) {
            $token = $tokens[$i];
            if (isset($map[$token->id]) && $i + 3 < $c && $tokens[$i + 1]->text === '(' && $tokens[$i + 2]->id === \T_STRING && \strtolower($tokens[$i + 2]->text) === 'set' && $tokens[$i + 3]->text === ')' && $this->is_keyword_context($tokens, $i)) {
                array_splice($tokens, $i, 4, [new Token($map[$token->id], $token->text . '(' . $tokens[$i + 2]->text . ')', $token->line, $token->pos)]);
                $c -= 3;
            }
        }
        return $tokens;
    }
    public function reverse_emulate(string $code, array $tokens): array
    {
        $reverse_map = [\T_PUBLIC_SET => \T_PUBLIC, \T_PROTECTED_SET => \T_PROTECTED, \T_PRIVATE_SET => \T_PRIVATE];
        for ($i = 0, $c = count($tokens); $i < $c; ++$i) {
            $token = $tokens[$i];
            if (isset($reverse_map[$token->id]) && \preg_match('/(public|protected|private)\((set)\)/i', $token->text, $matches)) {
                [, $modifier, $set] = $matches;
                $modifier_len = \strlen($modifier);
                array_splice($tokens, $i, 1, [new Token($reverse_map[$token->id], $modifier, $token->line, $token->pos), new Token(\ord('('), '(', $token->line, $token->pos + $modifier_len), new Token(\T_STRING, $set, $token->line, $token->pos + $modifier_len + 1), new Token(\ord(')'), ')', $token->line, $token->pos + $modifier_len + 4)]);
                $i += 3;
                $c += 3;
            }
        }
        return $tokens;
    }
    /** @param Token[] $tokens */
    protected function is_keyword_context(array $tokens, int $pos): bool
    {
        $prev_token = $this->get_previous_non_space_token($tokens, $pos);
        if ($prev_token === null) {
            return false;
        }
        return $prev_token->id !== \T_OBJECT_OPERATOR && $prev_token->id !== \T_NULLSAFE_OBJECT_OPERATOR;
    }
    /** @param Token[] $tokens */
    private function get_previous_non_space_token(array $tokens, int $start): ?Token
    {
        for ($i = $start - 1; $i >= 0; --$i) {
            if ($tokens[$i]->id === T_WHITESPACE) {
                continue;
            }
            return $tokens[$i];
        }
        return null;
    }
}