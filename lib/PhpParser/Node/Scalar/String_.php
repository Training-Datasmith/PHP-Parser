<?php

declare (strict_types=1);
namespace Php_Parser\Node\Scalar;

use Php_Parser\Error;
use Php_Parser\Node\Scalar;
class String_ extends Scalar
{
    /* For use in "kind" attribute */
    public const KIND_SINGLE_QUOTED = 1;
    public const KIND_DOUBLE_QUOTED = 2;
    public const KIND_HEREDOC = 3;
    public const KIND_NOWDOC = 4;
    /** @var string String value */
    public string $value;
    /** @var array<string, string> Escaped character to its decoded value */
    protected static array $replacements = ['\\' => '\\', '$' => '$', 'n' => "\n", 'r' => "\r", 't' => "\t", 'f' => "\f", 'v' => "\v", 'e' => "\x1b"];
    /**
     * Constructs a string scalar node.
     *
     * @param string $value Value of the string
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(string $value, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->value = $value;
    }
    public function get_sub_node_names(): array
    {
        return ['value'];
    }
    /**
     * @param array<string, mixed> $attributes
     * @param bool $parseUnicodeEscape Whether to parse PHP 7 \u escapes
     */
    public static function from_string(string $str, array $attributes = [], bool $parse_unicode_escape = true): self
    {
        $attributes['kind'] = $str[0] === "'" || $str[1] === "'" && ($str[0] === 'b' || $str[0] === 'B') ? Scalar\String_::KIND_SINGLE_QUOTED : Scalar\String_::KIND_DOUBLE_QUOTED;
        $attributes['rawValue'] = $str;
        $string = self::parse($str, $parse_unicode_escape);
        return new self($string, $attributes);
    }
    /**
     * @internal
     *
     * Parses a string token.
     *
     * @param string $str String token content
     * @param bool $parseUnicodeEscape Whether to parse PHP 7 \u escapes
     *
     * @return string The parsed string
     */
    public static function parse(string $str, bool $parse_unicode_escape = true): string
    {
        $b_length = 0;
        if ('b' === $str[0] || 'B' === $str[0]) {
            $b_length = 1;
        }
        if ('\'' === $str[$b_length]) {
            return str_replace(['\\\\', '\\\''], ['\\', '\''], substr($str, $b_length + 1, -1));
        }
        return self::parse_escape_sequences(substr($str, $b_length + 1, -1), '"', $parse_unicode_escape);
    }
    /**
     * @internal
     *
     * Parses escape sequences in strings (all string types apart from single quoted).
     *
     * @param string $str String without quotes
     * @param null|string $quote Quote type
     * @param bool $parseUnicodeEscape Whether to parse PHP 7 \u escapes
     *
     * @return string String with escape sequences parsed
     */
    public static function parse_escape_sequences(string $str, ?string $quote, bool $parse_unicode_escape = true): string
    {
        if (null !== $quote) {
            $str = str_replace('\\' . $quote, $quote, $str);
        }
        $extra = '';
        if ($parse_unicode_escape) {
            $extra = '|u\{([0-9a-fA-F]+)\}';
        }
        return preg_replace_callback('~\\\\([\\\\$nrtfve]|[xX][0-9a-fA-F]{1,2}|[0-7]{1,3}' . $extra . ')~', function ($matches) {
            $str = $matches[1];
            if (isset(self::$replacements[$str])) {
                return self::$replacements[$str];
            }
            if ('x' === $str[0] || 'X' === $str[0]) {
                return chr(hexdec(substr($str, 1)));
            }
            if ('u' === $str[0]) {
                $dec = hexdec($matches[2]);
                // If it overflowed to float, treat as INT_MAX, it will throw an error anyway.
                return self::code_point_to_utf8(\is_int($dec) ? $dec : \PHP_INT_MAX);
            }
            return chr(octdec($str) & 255);
        }, $str);
    }
    /**
     * Converts a Unicode code point to its UTF-8 encoded representation.
     *
     * @param int $num Code point
     *
     * @return string UTF-8 representation of code point
     */
    private static function code_point_to_utf8(int $num): string
    {
        if ($num <= 0x7f) {
            return chr($num);
        }
        if ($num <= 0x7ff) {
            return chr(($num >> 6) + 0xc0) . chr(($num & 0x3f) + 0x80);
        }
        if ($num <= 0xffff) {
            return chr(($num >> 12) + 0xe0) . chr(($num >> 6 & 0x3f) + 0x80) . chr(($num & 0x3f) + 0x80);
        }
        if ($num <= 0x1fffff) {
            return chr(($num >> 18) + 0xf0) . chr(($num >> 12 & 0x3f) + 0x80) . chr(($num >> 6 & 0x3f) + 0x80) . chr(($num & 0x3f) + 0x80);
        }
        throw new Error('Invalid UTF-8 codepoint escape sequence: Codepoint too large');
    }
    public function get_type(): string
    {
        return 'Scalar_String';
    }
}