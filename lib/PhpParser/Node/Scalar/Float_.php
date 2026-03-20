<?php

declare (strict_types=1);
namespace Php_Parser\Node\Scalar;

use Php_Parser\Node\Scalar;
class Float_ extends Scalar
{
    /** @var float Number value */
    public float $value;
    /**
     * Constructs a float number scalar node.
     *
     * @param float $value Value of the number
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(float $value, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->value = $value;
    }
    public function get_sub_node_names(): array
    {
        return ['value'];
    }
    /**
     * @param mixed[] $attributes
     */
    public static function from_string(string $str, array $attributes = []): Float_
    {
        $attributes['rawValue'] = $str;
        $float = self::parse($str);
        return new Float_($float, $attributes);
    }
    /**
     * @internal
     *
     * Parses a DNUMBER token like PHP would.
     *
     * @param string $str A string number
     *
     * @return float The parsed number
     */
    public static function parse(string $str): float
    {
        $str = str_replace('_', '', $str);
        // Check whether this is one of the special integer notations.
        if ('0' === $str[0]) {
            // hex
            if ('x' === $str[1] || 'X' === $str[1]) {
                return hexdec($str);
            }
            // bin
            if ('b' === $str[1] || 'B' === $str[1]) {
                return bindec($str);
            }
            // oct, but only if the string does not contain any of '.eE'.
            if (false === strpbrk($str, '.eE')) {
                // substr($str, 0, strcspn($str, '89')) cuts the string at the first invalid digit
                // (8 or 9) so that only the digits before that are used.
                return octdec(substr($str, 0, strcspn($str, '89')));
            }
        }
        // dec
        return (float) $str;
    }
    public function get_type(): string
    {
        return 'Scalar_Float';
    }
}
// @deprecated compatibility alias
class_alias(Float_::class, D_Number::class);