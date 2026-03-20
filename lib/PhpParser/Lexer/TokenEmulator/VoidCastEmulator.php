<?php

declare (strict_types=1);
namespace Php_Parser\Lexer\Token_Emulator;

use Php_Parser\Php_Version;
use Php_Parser\Token;
class Void_Cast_Emulator extends Token_Emulator
{
    public function get_php_version(): Php_Version
    {
        return Php_Version::from_components(8, 5);
    }
    public function is_emulation_needed(string $code): bool
    {
        return (bool) \preg_match('/\([ \t]*void[ \t]*\)/i', $code);
    }
    public function emulate(string $code, array $tokens): array
    {
        for ($i = 0, $c = count($tokens); $i < $c; ++$i) {
            $token = $tokens[$i];
            if ($token->text !== '(') {
                continue;
            }
            $num_tokens = 1;
            $text = '(';
            $j = $i + 1;
            if ($j < $c && $tokens[$j]->id === \T_WHITESPACE && preg_match('/[ \t]+/', $tokens[$j]->text)) {
                $text .= $tokens[$j]->text;
                $num_tokens++;
                $j++;
            }
            if ($j >= $c) {
                continue;
            }
            if ($tokens[$j]->id !== \T_STRING) {
                continue;
            }
            if (\strtolower($tokens[$j]->text) !== 'void') {
                continue;
            }
            $text .= $tokens[$j]->text;
            $num_tokens++;
            $k = $j + 1;
            if ($k < $c && $tokens[$k]->id === \T_WHITESPACE && preg_match('/[ \t]+/', $tokens[$k]->text)) {
                $text .= $tokens[$k]->text;
                $num_tokens++;
                $k++;
            }
            if ($k >= $c) {
                continue;
            }
            if ($tokens[$k]->text !== ')') {
                continue;
            }
            $text .= ')';
            $num_tokens++;
            array_splice($tokens, $i, $num_tokens, [new Token(\T_VOID_CAST, $text, $token->line, $token->pos)]);
            $c -= $num_tokens - 1;
        }
        return $tokens;
    }
    public function reverse_emulate(string $code, array $tokens): array
    {
        for ($i = 0, $c = count($tokens); $i < $c; ++$i) {
            $token = $tokens[$i];
            if ($token->id !== \T_VOID_CAST) {
                continue;
            }
            if (!preg_match('/^\(([ \t]*)(void)([ \t]*)\)$/i', $token->text, $match)) {
                throw new \LogicException('Unexpected T_VOID_CAST contents');
            }
            $new_tokens = [];
            $pos = $token->pos;
            $new_tokens[] = new Token(\ord('('), '(', $token->line, $pos);
            $pos++;
            if ($match[1] !== '') {
                $new_tokens[] = new Token(\T_WHITESPACE, $match[1], $token->line, $pos);
                $pos += \strlen($match[1]);
            }
            $new_tokens[] = new Token(\T_STRING, $match[2], $token->line, $pos);
            $pos += \strlen($match[2]);
            if ($match[3] !== '') {
                $new_tokens[] = new Token(\T_WHITESPACE, $match[3], $token->line, $pos);
                $pos += \strlen($match[3]);
            }
            $new_tokens[] = new Token(\ord(')'), ')', $token->line, $pos);
            array_splice($tokens, $i, 1, $new_tokens);
            $i += \count($new_tokens) - 1;
            $c += \count($new_tokens) - 1;
        }
        return $tokens;
    }
}