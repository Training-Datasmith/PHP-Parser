<?php

declare (strict_types=1);
namespace Php_Parser\Lexer\Token_Emulator;

use Php_Parser\Php_Version;
/**
 * Reverses emulation direction of the inner emulator.
 */
final class Reverse_Emulator extends Token_Emulator
{
    /** @var TokenEmulator Inner emulator */
    private Token_Emulator $emulator;
    public function __construct(Token_Emulator $emulator)
    {
        $this->emulator = $emulator;
    }
    public function get_php_version(): Php_Version
    {
        return $this->emulator->get_php_version();
    }
    public function is_emulation_needed(string $code): bool
    {
        return $this->emulator->is_emulation_needed($code);
    }
    public function emulate(string $code, array $tokens): array
    {
        return $this->emulator->reverse_emulate($code, $tokens);
    }
    public function reverse_emulate(string $code, array $tokens): array
    {
        return $this->emulator->emulate($code, $tokens);
    }
    public function preprocess_code(string $code, array &$patches): string
    {
        return $code;
    }
}