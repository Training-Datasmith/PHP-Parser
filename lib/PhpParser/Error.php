<?php

declare (strict_types=1);
namespace Php_Parser;

class Error extends \RuntimeException
{
    protected string $raw_message;
    /** @var array<string, mixed> */
    protected array $attributes;
    /**
     * Creates an Exception signifying a parse error.
     *
     * @param string $message Error message
     * @param array<string, mixed> $attributes Attributes of node/token where error occurred
     */
    public function __construct(string $message, array $attributes = [])
    {
        $this->raw_message = $message;
        $this->attributes = $attributes;
        $this->update_message();
    }
    /**
     * Gets the error message
     *
     * @return string Error message
     */
    public function get_raw_message(): string
    {
        return $this->raw_message;
    }
    /**
     * Gets the line the error starts in.
     *
     * @return int Error start line
     * @phpstan-return -1|positive-int
     */
    public function get_start_line(): int
    {
        return $this->attributes['startLine'] ?? -1;
    }
    /**
     * Gets the line the error ends in.
     *
     * @return int Error end line
     * @phpstan-return -1|positive-int
     */
    public function get_end_line(): int
    {
        return $this->attributes['endLine'] ?? -1;
    }
    /**
     * Gets the attributes of the node/token the error occurred at.
     *
     * @return array<string, mixed>
     */
    public function get_attributes(): array
    {
        return $this->attributes;
    }
    /**
     * Sets the attributes of the node/token the error occurred at.
     *
     * @param array<string, mixed> $attributes
     */
    public function set_attributes(array $attributes): void
    {
        $this->attributes = $attributes;
        $this->update_message();
    }
    /**
     * Sets the line of the PHP file the error occurred in.
     *
     * @param string $message Error message
     */
    public function set_raw_message(string $message): void
    {
        $this->raw_message = $message;
        $this->update_message();
    }
    /**
     * Sets the line the error starts in.
     *
     * @param int $line Error start line
     */
    public function set_start_line(int $line): void
    {
        $this->attributes['startLine'] = $line;
        $this->update_message();
    }
    /**
     * Returns whether the error has start and end column information.
     *
     * For column information enable the startFilePos and endFilePos in the lexer options.
     */
    public function has_column_info(): bool
    {
        return isset($this->attributes['startFilePos'], $this->attributes['endFilePos']);
    }
    /**
     * Gets the start column (1-based) into the line where the error started.
     *
     * @param string $code Source code of the file
     */
    public function get_start_column(string $code): int
    {
        if (!$this->has_column_info()) {
            throw new \RuntimeException('Error does not have column information');
        }
        return $this->to_column($code, $this->attributes['startFilePos']);
    }
    /**
     * Gets the end column (1-based) into the line where the error ended.
     *
     * @param string $code Source code of the file
     */
    public function get_end_column(string $code): int
    {
        if (!$this->has_column_info()) {
            throw new \RuntimeException('Error does not have column information');
        }
        return $this->to_column($code, $this->attributes['endFilePos']);
    }
    /**
     * Formats message including line and column information.
     *
     * @param string $code Source code associated with the error, for calculation of the columns
     *
     * @return string Formatted message
     */
    public function get_message_with_column_info(string $code): string
    {
        return sprintf('%s from %d:%d to %d:%d', $this->get_raw_message(), $this->get_start_line(), $this->get_start_column($code), $this->get_end_line(), $this->get_end_column($code));
    }
    /**
     * Converts a file offset into a column.
     *
     * @param string $code Source code that $pos indexes into
     * @param int $pos 0-based position in $code
     *
     * @return int 1-based column (relative to start of line)
     */
    private function to_column(string $code, int $pos): int
    {
        if ($pos > strlen($code)) {
            throw new \RuntimeException('Invalid position information');
        }
        $line_start_pos = strrpos($code, "\n", $pos - strlen($code));
        if (false === $line_start_pos) {
            $line_start_pos = -1;
        }
        return $pos - $line_start_pos;
    }
    /**
     * Updates the exception message after a change to rawMessage or rawLine.
     */
    protected function update_message(): void
    {
        $this->message = $this->raw_message;
        if (-1 === $this->get_start_line()) {
            $this->message .= ' on unknown line';
        } else {
            $this->message .= ' on line ' . $this->get_start_line();
        }
    }
}