<?php

declare (strict_types=1);
namespace Php_Parser\Lexer\Token_Emulator;

use Php_Parser\Token;
abstract class Keyword_Emulator extends Token_Emulator
{
    abstract public function get_keyword_string(): string;
    abstract public function get_keyword_token(): int;
    public function is_emulation_needed(string $code): bool
    {
        return strpos(strtolower($code), $this->get_keyword_string()) !== false;
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
    public function emulate(string $code, array $tokens): array
    {
        $keyword_string = $this->get_keyword_string();
        foreach ($tokens as $i => $token) {
            if ($token->id === T_STRING && strtolower($token->text) === $keyword_string && $this->is_keyword_context($tokens, $i)) {
                $token->id = $this->get_keyword_token();
            }
        }
        return $tokens;
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
    public function reverse_emulate(string $code, array $tokens): array
    {
        $keyword_token = $this->get_keyword_token();
        foreach ($tokens as $token) {
            if ($token->id === $keyword_token) {
                $token->id = \T_STRING;
            }
        }
        return $tokens;
    }
}