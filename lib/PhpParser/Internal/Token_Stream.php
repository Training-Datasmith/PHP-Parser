<?php

declare (strict_types=1);
namespace Php_Parser\Internal;

use Php_Parser\Token;
/**
 * Provides operations on token streams, for use by pretty printer.
 *
 * @internal
 */
class Token_Stream
{
    /** @var Token[] Tokens (in PhpToken::tokenize() format) */
    private array $tokens;
    /** @var int[] Map from position to indentation */
    private array $indent_map;
    /**
     * Create token stream instance.
     *
     * @param Token[] $tokens Tokens in PhpToken::tokenize() format
     */
    public function __construct(array $tokens, int $tab_width)
    {
        $this->tokens = $tokens;
        $this->indent_map = $this->calc_indent_map($tab_width);
    }
    /**
     * Whether the given position is immediately surrounded by parenthesis.
     *
     * @param int $startPos Start position
     * @param int $endPos End position
     */
    public function have_parens(int $start_pos, int $end_pos): bool
    {
        return $this->have_token_immediately_before($start_pos, '(') && $this->have_token_immediately_after($end_pos, ')');
    }
    /**
     * Whether the given position is immediately surrounded by braces.
     *
     * @param int $startPos Start position
     * @param int $endPos End position
     */
    public function have_braces(int $start_pos, int $end_pos): bool
    {
        return ($this->have_token_immediately_before($start_pos, '{') || $this->have_token_immediately_before($start_pos, T_CURLY_OPEN)) && $this->have_token_immediately_after($end_pos, '}');
    }
    /**
     * Check whether the position is directly preceded by a certain token type.
     *
     * During this check whitespace and comments are skipped.
     *
     * @param int $pos Position before which the token should occur
     * @param int|string $expectedTokenType Token to check for
     *
     * @return bool Whether the expected token was found
     */
    public function have_token_immediately_before(int $pos, $expected_token_type): bool
    {
        $tokens = $this->tokens;
        $pos--;
        for (; $pos >= 0; $pos--) {
            $token = $tokens[$pos];
            if ($token->is($expected_token_type)) {
                return true;
            }
            if (!$token->is_ignorable()) {
                break;
            }
        }
        return false;
    }
    /**
     * Check whether the position is directly followed by a certain token type.
     *
     * During this check whitespace and comments are skipped.
     *
     * @param int $pos Position after which the token should occur
     * @param int|string $expectedTokenType Token to check for
     *
     * @return bool Whether the expected token was found
     */
    public function have_token_immediately_after(int $pos, $expected_token_type): bool
    {
        $tokens = $this->tokens;
        $pos++;
        for ($c = \count($tokens); $pos < $c; $pos++) {
            $token = $tokens[$pos];
            if ($token->is($expected_token_type)) {
                return true;
            }
            if (!$token->is_ignorable()) {
                break;
            }
        }
        return false;
    }
    /** @param int|string|(int|string)[] $skipTokenType */
    public function skip_left(int $pos, $skip_token_type): int
    {
        $tokens = $this->tokens;
        $pos = $this->skip_left_whitespace($pos);
        if ($skip_token_type === \T_WHITESPACE) {
            return $pos;
        }
        if (!$tokens[$pos]->is($skip_token_type)) {
            // Shouldn't happen. The skip token MUST be there
            throw new \Exception('Encountered unexpected token');
        }
        $pos--;
        return $this->skip_left_whitespace($pos);
    }
    /** @param int|string|(int|string)[] $skipTokenType */
    public function skip_right(int $pos, $skip_token_type): int
    {
        $tokens = $this->tokens;
        $pos = $this->skip_right_whitespace($pos);
        if ($skip_token_type === \T_WHITESPACE) {
            return $pos;
        }
        if (!$tokens[$pos]->is($skip_token_type)) {
            // Shouldn't happen. The skip token MUST be there
            throw new \Exception('Encountered unexpected token');
        }
        $pos++;
        return $this->skip_right_whitespace($pos);
    }
    /**
     * Return first non-whitespace token position smaller or equal to passed position.
     *
     * @param int $pos Token position
     * @return int Non-whitespace token position
     */
    public function skip_left_whitespace(int $pos): int
    {
        $tokens = $this->tokens;
        for (; $pos >= 0; $pos--) {
            if (!$tokens[$pos]->is_ignorable()) {
                break;
            }
        }
        return $pos;
    }
    /**
     * Return first non-whitespace position greater or equal to passed position.
     *
     * @param int $pos Token position
     * @return int Non-whitespace token position
     */
    public function skip_right_whitespace(int $pos): int
    {
        $tokens = $this->tokens;
        for ($count = \count($tokens); $pos < $count; $pos++) {
            if (!$tokens[$pos]->is_ignorable()) {
                break;
            }
        }
        return $pos;
    }
    /** @param int|string|(int|string)[] $findTokenType */
    public function find_right(int $pos, $find_token_type): int
    {
        $tokens = $this->tokens;
        for ($count = \count($tokens); $pos < $count; $pos++) {
            if ($tokens[$pos]->is($find_token_type)) {
                return $pos;
            }
        }
        return -1;
    }
    /**
     * Whether the given position range contains a certain token type.
     *
     * @param int $startPos Starting position (inclusive)
     * @param int $endPos Ending position (exclusive)
     * @param int|string $tokenType Token type to look for
     * @return bool Whether the token occurs in the given range
     */
    public function have_token_in_range(int $start_pos, int $end_pos, $token_type): bool
    {
        $tokens = $this->tokens;
        for ($pos = $start_pos; $pos < $end_pos; $pos++) {
            if ($tokens[$pos]->is($token_type)) {
                return true;
            }
        }
        return false;
    }
    public function have_tag_in_range(int $start_pos, int $end_pos): bool
    {
        if ($this->have_token_in_range($start_pos, $end_pos, \T_OPEN_TAG)) {
            return true;
        }
        return $this->have_token_in_range($start_pos, $end_pos, \T_CLOSE_TAG);
    }
    /**
     * Get indentation before token position.
     *
     * @param int $pos Token position
     *
     * @return int Indentation depth (in spaces)
     */
    public function get_indentation_before(int $pos): int
    {
        return $this->indent_map[$pos];
    }
    /**
     * Get the code corresponding to a token offset range, optionally adjusted for indentation.
     *
     * @param int $from Token start position (inclusive)
     * @param int $to Token end position (exclusive)
     * @param int $indent By how much the code should be indented (can be negative as well)
     *
     * @return string Code corresponding to token range, adjusted for indentation
     */
    public function get_token_code(int $from, int $to, int $indent): string
    {
        $tokens = $this->tokens;
        $result = '';
        for ($pos = $from; $pos < $to; $pos++) {
            $token = $tokens[$pos];
            $id = $token->id;
            $text = $token->text;
            if ($id === \T_CONSTANT_ENCAPSED_STRING || $id === \T_ENCAPSED_AND_WHITESPACE) {
                $result .= $text;
            } else if ($indent < 0) {
                $result .= str_replace("\n" . str_repeat(' ', -$indent), "\n", $text);
            } elseif ($indent > 0) {
                $result .= str_replace("\n", "\n" . str_repeat(' ', $indent), $text);
            } else {
                $result .= $text;
            }
        }
        return $result;
    }
    /**
     * Precalculate the indentation at every token position.
     *
     * @return int[] Token position to indentation map
     */
    private function calc_indent_map(int $tab_width): array
    {
        $indent_map = [];
        $indent = 0;
        foreach ($this->tokens as $i => $token) {
            $indent_map[] = $indent;
            if ($token->id === \T_WHITESPACE) {
                $content = $token->text;
                $newline_pos = \strrpos($content, "\n");
                if (false !== $newline_pos) {
                    $indent = $this->get_indent(\substr($content, $newline_pos + 1), $tab_width);
                } elseif ($i === 1 && $this->tokens[0]->id === \T_OPEN_TAG && $this->tokens[0]->text[\strlen($this->tokens[0]->text) - 1] === "\n") {
                    // Special case: Newline at the end of opening tag followed by whitespace.
                    $indent = $this->get_indent($content, $tab_width);
                }
            }
        }
        // Add a sentinel for one past end of the file
        $indent_map[] = $indent;
        return $indent_map;
    }
    private function get_indent(string $ws, int $tab_width): int
    {
        $spaces = \substr_count($ws, ' ');
        $tabs = \substr_count($ws, "\t");
        assert(\strlen($ws) === $spaces + $tabs);
        return $spaces + $tabs * $tab_width;
    }
}