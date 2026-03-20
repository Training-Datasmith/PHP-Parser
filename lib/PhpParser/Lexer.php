<?php

declare (strict_types=1);
namespace Php_Parser;

require __DIR__ . '/compatibility_tokens.php';
class Lexer
{
    /**
     * Tokenize the provided source code.
     *
     * The token array is in the same format as provided by the PhpToken::tokenize() method in
     * PHP 8.0. The tokens are instances of PhpParser\Token, to abstract over a polyfill
     * implementation in earlier PHP version.
     *
     * The token array is terminated by a sentinel token with token ID 0.
     * The token array does not discard any tokens (i.e. whitespace and comments are included).
     * The token position attributes are against this token array.
     *
     * @param string $code The source code to tokenize.
     * @param ErrorHandler|null $errorHandler Error handler to use for lexing errors. Defaults to
     *                                        ErrorHandler\Throwing.
     * @return Token[] Tokens
     */
    public function tokenize(string $code, ?Error_Handler $error_handler = null): array
    {
        if (null === $error_handler) {
            $error_handler = new Error_Handler\Throwing();
        }
        $scream = ini_set('xdebug.scream', '0');
        $tokens = @Token::tokenize($code);
        $this->postprocess_tokens($tokens, $error_handler);
        if (false !== $scream) {
            ini_set('xdebug.scream', $scream);
        }
        return $tokens;
    }
    private function handle_invalid_character(Token $token, Error_Handler $error_handler): void
    {
        $chr = $token->text;
        if ($chr === "\x00") {
            // PHP cuts error message after null byte, so need special case
            $error_msg = 'Unexpected null byte';
        } else {
            $error_msg = sprintf('Unexpected character "%s" (ASCII %d)', $chr, ord($chr));
        }
        $error_handler->handle_error(new Error($error_msg, ['startLine' => $token->line, 'endLine' => $token->line, 'startFilePos' => $token->pos, 'endFilePos' => $token->pos]));
    }
    private function is_unterminated_comment(Token $token): bool
    {
        return $token->is([\T_COMMENT, \T_DOC_COMMENT]) && substr($token->text, 0, 2) === '/*' && substr($token->text, -2) !== '*/';
    }
    /**
     * @param list<Token> $tokens
     */
    protected function postprocess_tokens(array &$tokens, Error_Handler $error_handler): void
    {
        // This function reports errors (bad characters and unterminated comments) in the token
        // array, and performs certain canonicalizations:
        //  * Use PHP 8.1 T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG and
        //    T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG tokens used to disambiguate intersection types.
        //  * Add a sentinel token with ID 0.
        $num_tokens = \count($tokens);
        if ($num_tokens === 0) {
            // Empty input edge case: Just add the sentinel token.
            $tokens[] = new Token(0, "\x00", 1, 0);
            return;
        }
        for ($i = 0; $i < $num_tokens; $i++) {
            $token = $tokens[$i];
            if ($token->id === \T_BAD_CHARACTER) {
                $this->handle_invalid_character($token, $error_handler);
            }
            if ($token->id === \ord('&')) {
                $next = $i + 1;
                while (isset($tokens[$next]) && $tokens[$next]->id === \T_WHITESPACE) {
                    $next++;
                }
                $followed_by_var_or_var_arg = isset($tokens[$next]) && $tokens[$next]->is([\T_VARIABLE, \T_ELLIPSIS]);
                $token->id = $followed_by_var_or_var_arg ? \T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG : \T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG;
            }
        }
        // Check for unterminated comment
        $last_token = $tokens[$num_tokens - 1];
        if ($this->is_unterminated_comment($last_token)) {
            $error_handler->handle_error(new Error('Unterminated comment', ['startLine' => $last_token->line, 'endLine' => $last_token->get_end_line(), 'startFilePos' => $last_token->pos, 'endFilePos' => $last_token->get_end_pos()]));
        }
        // Add sentinel token.
        $tokens[] = new Token(0, "\x00", $last_token->get_end_line(), $last_token->get_end_pos());
    }
}