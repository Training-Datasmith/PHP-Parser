<?php

declare (strict_types=1);
namespace Php_Parser\Lexer\Token_Emulator;

use Php_Parser\Php_Version;
use Php_Parser\Token;
class Pipe_Operator_Emulator extends Token_Emulator
{
    public function get_php_version(): Php_Version
    {
        return Php_Version::from_components(8, 5);
    }
    public function is_emulation_needed(string $code): bool
    {
        return \strpos($code, '|>') !== false;
    }
    public function emulate(string $code, array $tokens): array
    {
        for ($i = 0, $c = count($tokens); $i < $c; ++$i) {
            $token = $tokens[$i];
            if ($token->text === '|' && isset($tokens[$i + 1]) && $tokens[$i + 1]->text === '>') {
                array_splice($tokens, $i, 2, [new Token(\T_PIPE, '|>', $token->line, $token->pos)]);
                $c--;
            }
        }
        return $tokens;
    }
    public function reverse_emulate(string $code, array $tokens): array
    {
        for ($i = 0, $c = count($tokens); $i < $c; ++$i) {
            $token = $tokens[$i];
            if ($token->id === \T_PIPE) {
                array_splice($tokens, $i, 1, [new Token(\ord('|'), '|', $token->line, $token->pos), new Token(\ord('>'), '>', $token->line, $token->pos + 1)]);
                $i++;
                $c++;
            }
        }
        return $tokens;
    }
}