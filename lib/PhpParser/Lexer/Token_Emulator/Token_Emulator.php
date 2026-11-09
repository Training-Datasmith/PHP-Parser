<?php

declare (strict_types=1);
namespace Php_Parser\Lexer\Token_Emulator;

use Php_Parser\Php_Version;
use Php_Parser\Token;
/** @internal */
abstract class Token_Emulator
{
    abstract public function get_php_version(): Php_Version;
    abstract public function is_emulation_needed(string $code): bool;
    /**
     * @param Token[] $tokens Original tokens
     * @return Token[] Modified Tokens
     */
    abstract public function emulate(string $code, array $tokens): array;
    /**
     * @param Token[] $tokens Original tokens
     * @return Token[] Modified Tokens
     */
    abstract public function reverse_emulate(string $code, array $tokens): array;
    /** @param array{int, string, string}[] $patches */
    public function preprocess_code(string $code, array &$patches): string
    {
        return $code;
    }
}