<?php

declare (strict_types=1);
namespace Php_Parser;

class Comment implements \JsonSerializable
{
    protected string $text;
    protected int $start_line;
    protected int $start_file_pos;
    protected int $start_token_pos;
    protected int $end_line;
    protected int $end_file_pos;
    protected int $end_token_pos;
    /**
     * Constructs a comment node.
     *
     * @param string $text Comment text (including comment delimiters like /*)
     * @param int $startLine Line number the comment started on
     * @param int $startFilePos File offset the comment started on
     * @param int $startTokenPos Token offset the comment started on
     */
    public function __construct(string $text, int $start_line = -1, int $start_file_pos = -1, int $start_token_pos = -1, int $end_line = -1, int $end_file_pos = -1, int $end_token_pos = -1)
    {
        $this->text = $text;
        $this->start_line = $start_line;
        $this->start_file_pos = $start_file_pos;
        $this->start_token_pos = $start_token_pos;
        $this->end_line = $end_line;
        $this->end_file_pos = $end_file_pos;
        $this->end_token_pos = $end_token_pos;
    }
    /**
     * Gets the comment text.
     *
     * @return string The comment text (including comment delimiters like /*)
     */
    public function get_text(): string
    {
        return $this->text;
    }
    /**
     * Gets the line number the comment started on.
     *
     * @return int Line number (or -1 if not available)
     * @phpstan-return -1|positive-int
     */
    public function get_start_line(): int
    {
        return $this->start_line;
    }
    /**
     * Gets the file offset the comment started on.
     *
     * @return int File offset (or -1 if not available)
     */
    public function get_start_file_pos(): int
    {
        return $this->start_file_pos;
    }
    /**
     * Gets the token offset the comment started on.
     *
     * @return int Token offset (or -1 if not available)
     */
    public function get_start_token_pos(): int
    {
        return $this->start_token_pos;
    }
    /**
     * Gets the line number the comment ends on.
     *
     * @return int Line number (or -1 if not available)
     * @phpstan-return -1|positive-int
     */
    public function get_end_line(): int
    {
        return $this->end_line;
    }
    /**
     * Gets the file offset the comment ends on.
     *
     * @return int File offset (or -1 if not available)
     */
    public function get_end_file_pos(): int
    {
        return $this->end_file_pos;
    }
    /**
     * Gets the token offset the comment ends on.
     *
     * @return int Token offset (or -1 if not available)
     */
    public function get_end_token_pos(): int
    {
        return $this->end_token_pos;
    }
    /**
     * Gets the comment text.
     *
     * @return string The comment text (including comment delimiters like /*)
     */
    public function __toString(): string
    {
        return $this->text;
    }
    /**
     * Gets the reformatted comment text.
     *
     * "Reformatted" here means that we try to clean up the whitespace at the
     * starts of the lines. This is necessary because we receive the comments
     * without leading whitespace on the first line, but with leading whitespace
     * on all subsequent lines.
     *
     * Additionally, this normalizes CRLF newlines to LF newlines.
     */
    public function get_reformatted_text(): string
    {
        $text = str_replace("\r\n", "\n", $this->text);
        $newline_pos = strpos($text, "\n");
        if (false === $newline_pos) {
            // Single line comments don't need further processing
            return $text;
        }
        if (preg_match('(^.*(?:\n\s+\*.*)+$)', $text)) {
            // Multi line comment of the type
            //
            //     /*
            //      * Some text.
            //      * Some more text.
            //      */
            //
            // is handled by replacing the whitespace sequences before the * by a single space
            return preg_replace('(^\s+\*)m', ' *', $text);
        }
        if (preg_match('(^/\*\*?\s*\n)', $text) && preg_match('(\n(\s*)\*/$)', $text, $matches)) {
            // Multi line comment of the type
            //
            //    /*
            //        Some text.
            //        Some more text.
            //    */
            //
            // is handled by removing the whitespace sequence on the line before the closing
            // */ on all lines. So if the last line is "    */", then "    " is removed at the
            // start of all lines.
            return preg_replace('(^' . preg_quote($matches[1]) . ')m', '', $text);
        }
        if (preg_match('(^/\*\*?\s*(?!\s))', $text, $matches)) {
            // Multi line comment of the type
            //
            //     /* Some text.
            //        Some more text.
            //          Indented text.
            //        Even more text. */
            //
            // is handled by removing the difference between the shortest whitespace prefix on all
            // lines and the length of the "/* " opening sequence.
            $prefix_len = $this->get_shortest_whitespace_prefix_len(substr($text, $newline_pos + 1));
            $remove_len = $prefix_len - strlen($matches[0]);
            return preg_replace('(^\s{' . $remove_len . '})m', '', $text);
        }
        // No idea how to format this comment, so simply return as is
        return $text;
    }
    /**
     * Get length of shortest whitespace prefix (at the start of a line).
     *
     * If there is a line with no prefix whitespace, 0 is a valid return value.
     *
     * @param string $str String to check
     * @return int Length in characters. Tabs count as single characters.
     */
    private function get_shortest_whitespace_prefix_len(string $str): int
    {
        $lines = explode("\n", $str);
        $shortest_prefix_len = \PHP_INT_MAX;
        foreach ($lines as $line) {
            preg_match('(^\s*)', $line, $matches);
            $prefix_len = strlen($matches[0]);
            if ($prefix_len < $shortest_prefix_len) {
                $shortest_prefix_len = $prefix_len;
            }
        }
        return $shortest_prefix_len;
    }
    /**
     * @return array{nodeType:string, text:mixed, line:mixed, filePos:mixed}
     */
    public function jsonSerialize(): array
    {
        // Technically not a node, but we make it look like one anyway
        $type = $this instanceof Comment\Doc ? 'Comment_Doc' : 'Comment';
        return [
            'nodeType' => $type,
            'text' => $this->text,
            // TODO: Rename these to include "start".
            'line' => $this->start_line,
            'filePos' => $this->start_file_pos,
            'tokenPos' => $this->start_token_pos,
            'endLine' => $this->end_line,
            'endFilePos' => $this->end_file_pos,
            'endTokenPos' => $this->end_token_pos,
        ];
    }
}