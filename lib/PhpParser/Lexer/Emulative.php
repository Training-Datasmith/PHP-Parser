<?php

declare (strict_types=1);
namespace Php_Parser\Lexer;

use Php_Parser\Error;
use Php_Parser\Error_Handler;
use Php_Parser\Lexer;
use Php_Parser\Lexer\Token_Emulator\Asymmetric_Visibility_Token_Emulator;
use Php_Parser\Lexer\Token_Emulator\Attribute_Emulator;
use Php_Parser\Lexer\Token_Emulator\Enum_Token_Emulator;
use Php_Parser\Lexer\Token_Emulator\Explicit_Octal_Emulator;
use Php_Parser\Lexer\Token_Emulator\Fn_Token_Emulator;
use Php_Parser\Lexer\Token_Emulator\Match_Token_Emulator;
use Php_Parser\Lexer\Token_Emulator\Nullsafe_Token_Emulator;
use Php_Parser\Lexer\Token_Emulator\Pipe_Operator_Emulator;
use Php_Parser\Lexer\Token_Emulator\Property_Token_Emulator;
use Php_Parser\Lexer\Token_Emulator\Readonly_Function_Token_Emulator;
use Php_Parser\Lexer\Token_Emulator\Readonly_Token_Emulator;
use Php_Parser\Lexer\Token_Emulator\Reverse_Emulator;
use Php_Parser\Lexer\Token_Emulator\Token_Emulator;
use Php_Parser\Lexer\Token_Emulator\Void_Cast_Emulator;
use Php_Parser\Php_Version;
use Php_Parser\Token;
class Emulative extends Lexer
{
    /** @var array{int, string, string}[] Patches used to reverse changes introduced in the code */
    private array $patches = [];
    /** @var list<TokenEmulator> */
    private array $emulators = [];
    private Php_Version $target_php_version;
    private Php_Version $host_php_version;
    /**
     * @param PhpVersion|null $phpVersion PHP version to emulate. Defaults to newest supported.
     */
    public function __construct(?Php_Version $php_version = null)
    {
        $this->target_php_version = $php_version ?? Php_Version::get_newest_supported();
        $this->host_php_version = Php_Version::get_host_version();
        $emulators = [new Fn_Token_Emulator(), new Match_Token_Emulator(), new Nullsafe_Token_Emulator(), new Attribute_Emulator(), new Enum_Token_Emulator(), new Readonly_Token_Emulator(), new Explicit_Octal_Emulator(), new Readonly_Function_Token_Emulator(), new Property_Token_Emulator(), new Asymmetric_Visibility_Token_Emulator(), new Pipe_Operator_Emulator(), new Void_Cast_Emulator()];
        // Collect emulators that are relevant for the PHP version we're running
        // and the PHP version we're targeting for emulation.
        foreach ($emulators as $emulator) {
            $emulator_php_version = $emulator->get_php_version();
            if ($this->is_forward_emulation_needed($emulator_php_version)) {
                $this->emulators[] = $emulator;
            } elseif ($this->is_reverse_emulation_needed($emulator_php_version)) {
                $this->emulators[] = new Reverse_Emulator($emulator);
            }
        }
    }
    public function tokenize(string $code, ?Error_Handler $error_handler = null): array
    {
        $emulators = array_filter($this->emulators, fn(\Php_Parser\Lexer\Token_Emulator\Token_Emulator $emulator): bool => $emulator->is_emulation_needed($code));
        if (empty($emulators)) {
            // Nothing to emulate, yay
            return parent::tokenize($code, $error_handler);
        }
        if ($error_handler === null) {
            $error_handler = new Error_Handler\Throwing();
        }
        $this->patches = [];
        foreach ($emulators as $emulator) {
            $code = $emulator->preprocess_code($code, $this->patches);
        }
        $collector = new Error_Handler\Collecting();
        $tokens = parent::tokenize($code, $collector);
        $this->sort_patches();
        $tokens = $this->fixup_tokens($tokens);
        $errors = $collector->get_errors();
        if (!empty($errors)) {
            $this->fixup_errors($errors);
            foreach ($errors as $error) {
                $error_handler->handle_error($error);
            }
        }
        foreach ($emulators as $emulator) {
            $tokens = $emulator->emulate($code, $tokens);
        }
        return $tokens;
    }
    private function is_forward_emulation_needed(Php_Version $emulator_php_version): bool
    {
        return $this->host_php_version->older($emulator_php_version) && $this->target_php_version->newer_or_equal($emulator_php_version);
    }
    private function is_reverse_emulation_needed(Php_Version $emulator_php_version): bool
    {
        return $this->host_php_version->newer_or_equal($emulator_php_version) && $this->target_php_version->older($emulator_php_version);
    }
    private function sort_patches(): void
    {
        // Patches may be contributed by different emulators.
        // Make sure they are sorted by increasing patch position.
        usort($this->patches, fn(array $p1, array $p2): int => $p1[0] <=> $p2[0]);
    }
    /**
     * @param list<Token> $tokens
     * @return list<Token>
     */
    private function fixup_tokens(array $tokens): array
    {
        if (\count($this->patches) === 0) {
            return $tokens;
        }
        // Load first patch
        $patch_idx = 0;
        [$patch_pos, $patch_type, $patch_text] = $this->patches[$patch_idx];
        // We use a manual loop over the tokens, because we modify the array on the fly
        $pos_delta = 0;
        $line_delta = 0;
        for ($i = 0, $c = \count($tokens); $i < $c; $i++) {
            $token = $tokens[$i];
            $pos = $token->pos;
            $token->pos += $pos_delta;
            $token->line += $line_delta;
            $local_pos_delta = 0;
            $len = \strlen($token->text);
            while ($patch_pos >= $pos && $patch_pos < $pos + $len) {
                $patch_text_len = \strlen($patch_text);
                if ($patch_type === 'remove') {
                    if ($patch_pos === $pos && $patch_text_len === $len) {
                        // Remove token entirely
                        array_splice($tokens, $i, 1, []);
                        $i--;
                        $c--;
                    } else {
                        // Remove from token string
                        $token->text = substr_replace($token->text, '', $patch_pos - $pos + $local_pos_delta, $patch_text_len);
                        $local_pos_delta -= $patch_text_len;
                    }
                    $line_delta -= \substr_count($patch_text, "\n");
                } elseif ($patch_type === 'add') {
                    // Insert into the token string
                    $token->text = substr_replace($token->text, $patch_text, $patch_pos - $pos + $local_pos_delta, 0);
                    $local_pos_delta += $patch_text_len;
                    $line_delta += \substr_count($patch_text, "\n");
                } elseif ($patch_type === 'replace') {
                    // Replace inside the token string
                    $token->text = substr_replace($token->text, $patch_text, $patch_pos - $pos + $local_pos_delta, $patch_text_len);
                } else {
                    assert(false);
                }
                // Fetch the next patch
                $patch_idx++;
                if ($patch_idx >= \count($this->patches)) {
                    // No more patches. However, we still need to adjust position.
                    $patch_pos = \PHP_INT_MAX;
                    break;
                }
                [$patch_pos, $patch_type, $patch_text] = $this->patches[$patch_idx];
            }
            $pos_delta += $local_pos_delta;
        }
        return $tokens;
    }
    /**
     * Fixup line and position information in errors.
     *
     * @param Error[] $errors
     */
    private function fixup_errors(array $errors): void
    {
        foreach ($errors as $error) {
            $attrs = $error->get_attributes();
            $pos_delta = 0;
            $line_delta = 0;
            foreach ($this->patches as $patch) {
                [$patch_pos, $patch_type, $patch_text] = $patch;
                if ($patch_pos >= $attrs['startFilePos']) {
                    // No longer relevant
                    break;
                }
                if ($patch_type === 'add') {
                    $pos_delta += strlen($patch_text);
                    $line_delta += substr_count($patch_text, "\n");
                } elseif ($patch_type === 'remove') {
                    $pos_delta -= strlen($patch_text);
                    $line_delta -= substr_count($patch_text, "\n");
                }
            }
            $attrs['startFilePos'] += $pos_delta;
            $attrs['endFilePos'] += $pos_delta;
            $attrs['startLine'] += $line_delta;
            $attrs['endLine'] += $line_delta;
            $error->set_attributes($attrs);
        }
    }
}