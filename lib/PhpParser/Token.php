<?php

declare (strict_types=1);
namespace Php_Parser;

/**
 * A PHP token. On PHP 8.0 this extends from PhpToken.
 */
class Token extends Internal\Token_Polyfill
{
    /** Get (exclusive) zero-based end position of the token. */
    public function get_end_pos(): int
    {
        return $this->pos + \strlen($this->text);
    }
    /** Get 1-based end line number of the token. */
    public function get_end_line(): int
    {
        return $this->line + \substr_count($this->text, "\n");
    }
}