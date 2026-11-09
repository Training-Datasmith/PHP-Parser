<?php

declare (strict_types=1);
namespace Php_Parser\Pretty_Printer;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Assign_Op;
use Php_Parser\Node\Expr\Binary_Op;
use Php_Parser\Node\Expr\Cast;
use Php_Parser\Node\Name;
use Php_Parser\Node\Scalar;
use Php_Parser\Node\Scalar\Magic_Const;
use Php_Parser\Node\Stmt;
use Php_Parser\Pretty_Printer_Abstract;
class Standard extends Pretty_Printer_Abstract
{
    // Special nodes
    protected function p_param(Node\Param $node): string
    {
        return $this->p_attr_groups($node->attr_groups, $this->php_version->supports_attributes()) . $this->p_modifiers($node->flags) . ($node->type ? $this->p($node->type) . ' ' : '') . ($node->by_ref ? '&' : '') . ($node->variadic ? '...' : '') . $this->p($node->var) . ($node->default ? ' = ' . $this->p($node->default) : '') . ($node->hooks ? ' {' . $this->p_stmts($node->hooks) . $this->nl . '}' : '');
    }
    protected function p_arg(Node\Arg $node): string
    {
        return ($node->name ? $node->name->to_string() . ': ' : '') . ($node->by_ref ? '&' : '') . ($node->unpack ? '...' : '') . $this->p($node->value);
    }
    protected function p_variadic_placeholder(Node\Variadic_Placeholder $node): string
    {
        return '...';
    }
    protected function p_const(Node\Const_ $node): string
    {
        return $node->name . ' = ' . $this->p($node->value);
    }
    protected function p_nullable_type(Node\Nullable_Type $node): string
    {
        return '?' . $this->p($node->type);
    }
    protected function p_union_type(Node\Union_Type $node): string
    {
        $types = [];
        foreach ($node->types as $type_node) {
            if ($type_node instanceof Node\Intersection_Type) {
                $types[] = '(' . $this->p($type_node) . ')';
                continue;
            }
            $types[] = $this->p($type_node);
        }
        return implode('|', $types);
    }
    protected function p_intersection_type(Node\Intersection_Type $node): string
    {
        return $this->p_implode($node->types, '&');
    }
    protected function p_identifier(Node\Identifier $node): string
    {
        return $node->name;
    }
    protected function p_var_like_identifier(Node\Var_Like_Identifier $node): string
    {
        return '$' . $node->name;
    }
    protected function p_attribute(Node\Attribute $node): string
    {
        return $this->p($node->name) . ($node->args ? '(' . $this->p_comma_separated($node->args) . ')' : '');
    }
    protected function p_attribute_group(Node\Attribute_Group $node): string
    {
        return '#[' . $this->p_comma_separated($node->attrs) . ']';
    }
    // Names
    protected function p_name(Name $node): string
    {
        return $node->name;
    }
    protected function p_name_fully_qualified(Name\Fully_Qualified $node): string
    {
        return '\\' . $node->name;
    }
    protected function p_name_relative(Name\Relative $node): string
    {
        return 'namespace\\' . $node->name;
    }
    // Magic Constants
    protected function p_scalar_magic_const_class(Magic_Const\Class_ $node): string
    {
        return '__CLASS__';
    }
    protected function p_scalar_magic_const_dir(Magic_Const\Dir $node): string
    {
        return '__DIR__';
    }
    protected function p_scalar_magic_const_file(Magic_Const\File $node): string
    {
        return '__FILE__';
    }
    protected function p_scalar_magic_const_function(Magic_Const\Function_ $node): string
    {
        return '__FUNCTION__';
    }
    protected function p_scalar_magic_const_line(Magic_Const\Line $node): string
    {
        return '__LINE__';
    }
    protected function p_scalar_magic_const_method(Magic_Const\Method $node): string
    {
        return '__METHOD__';
    }
    protected function p_scalar_magic_const_namespace(Magic_Const\Namespace_ $node): string
    {
        return '__NAMESPACE__';
    }
    protected function p_scalar_magic_const_trait(Magic_Const\Trait_ $node): string
    {
        return '__TRAIT__';
    }
    protected function p_scalar_magic_const_property(Magic_Const\Property $node): string
    {
        return '__PROPERTY__';
    }
    // Scalars
    private function indent_string(string $str): string
    {
        return str_replace("\n", $this->nl, $str);
    }
    protected function p_scalar_string(Scalar\String_ $node): string
    {
        $kind = $node->get_attribute('kind', Scalar\String_::KIND_SINGLE_QUOTED);
        switch ($kind) {
            case Scalar\String_::KIND_NOWDOC:
                $label = $node->get_attribute('docLabel');
                if ($label && !$this->contains_end_label($node->value, $label)) {
                    $should_ident = $this->php_version->supports_flexible_heredoc();
                    $nl = $should_ident ? $this->nl : $this->newline;
                    if ($node->value === '') {
                        return "<<<'{$label}'{$nl}{$label}{$this->doc_string_end_token}";
                    }
                    // Make sure trailing \r is not combined with following \n into CRLF.
                    if ($node->value[strlen($node->value) - 1] !== "\r") {
                        $value = $should_ident ? $this->indent_string($node->value) : $node->value;
                        return "<<<'{$label}'{$nl}{$value}{$nl}{$label}{$this->doc_string_end_token}";
                    }
                }
            /* break missing intentionally */
            // no break
            case Scalar\String_::KIND_SINGLE_QUOTED:
                return $this->p_single_quoted_string($node->value);
            case Scalar\String_::KIND_HEREDOC:
                $label = $node->get_attribute('docLabel');
                $escaped = $this->escape_string($node->value, null);
                if ($label && !$this->contains_end_label($escaped, $label)) {
                    $nl = $this->php_version->supports_flexible_heredoc() ? $this->nl : $this->newline;
                    if ($escaped === '') {
                        return "<<<{$label}{$nl}{$label}{$this->doc_string_end_token}";
                    }
                    return "<<<{$label}{$nl}{$escaped}{$nl}{$label}{$this->doc_string_end_token}";
                }
            /* break missing intentionally */
            // no break
            case Scalar\String_::KIND_DOUBLE_QUOTED:
                return '"' . $this->escape_string($node->value, '"') . '"';
        }
        throw new \Exception('Invalid string kind');
    }
    protected function p_scalar_interpolated_string(Scalar\Interpolated_String $node): string
    {
        if ($node->get_attribute('kind') === Scalar\String_::KIND_HEREDOC) {
            $label = $node->get_attribute('docLabel');
            if ($label && !$this->encapsed_contains_end_label($node->parts, $label)) {
                $nl = $this->php_version->supports_flexible_heredoc() ? $this->nl : $this->newline;
                if (count($node->parts) === 1 && $node->parts[0] instanceof Node\Interpolated_String_Part && $node->parts[0]->value === '') {
                    return "<<<{$label}{$nl}{$label}{$this->doc_string_end_token}";
                }
                return "<<<{$label}{$nl}" . $this->p_encaps_list($node->parts, null) . "{$nl}{$label}{$this->doc_string_end_token}";
            }
        }
        return '"' . $this->p_encaps_list($node->parts, '"') . '"';
    }
    protected function p_scalar_int(Scalar\Int_ $node): string
    {
        if ($node->get_attribute('shouldPrintRawValue') === true) {
            return $node->get_attribute('rawValue');
        }
        if ($node->value === -\PHP_INT_MAX - 1) {
            // PHP_INT_MIN cannot be represented as a literal,
            // because the sign is not part of the literal
            return '(-' . \PHP_INT_MAX . '-1)';
        }
        $kind = $node->get_attribute('kind', Scalar\Int_::KIND_DEC);
        if (Scalar\Int_::KIND_DEC === $kind) {
            return (string) $node->value;
        }
        if ($node->value < 0) {
            $sign = '-';
            $str = (string) -$node->value;
        } else {
            $sign = '';
            $str = (string) $node->value;
        }
        switch ($kind) {
            case Scalar\Int_::KIND_BIN:
                return $sign . '0b' . base_convert($str, 10, 2);
            case Scalar\Int_::KIND_OCT:
                return $sign . '0' . base_convert($str, 10, 8);
            case Scalar\Int_::KIND_HEX:
                return $sign . '0x' . base_convert($str, 10, 16);
        }
        throw new \Exception('Invalid number kind');
    }
    protected function p_scalar_float(Scalar\Float_ $node): string
    {
        if (!is_finite($node->value)) {
            if ($node->value === \INF) {
                return '1.0E+1000';
            }
            if ($node->value === -\INF) {
                return '-1.0E+1000';
            }
            return '\NAN';
        }
        // Try to find a short full-precision representation
        $string_value = sprintf('%.16G', $node->value);
        if ($node->value !== (float) $string_value) {
            $string_value = sprintf('%.17G', $node->value);
        }
        // %G is locale dependent and there exists no locale-independent alternative. We don't want
        // mess with switching locales here, so let's assume that a comma is the only non-standard
        // decimal separator we may encounter...
        $string_value = str_replace(',', '.', $string_value);
        // ensure that number is really printed as float
        return preg_match('/^-?[0-9]+$/', $string_value) ? $string_value . '.0' : $string_value;
    }
    // Assignments
    protected function p_expr_assign(Expr\Assign $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Expr\Assign::class, $this->p($node->var) . ' = ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_assign_ref(Expr\Assign_Ref $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Expr\Assign_Ref::class, $this->p($node->var) . ' =& ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_assign_op_plus(Assign_Op\Plus $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Assign_Op\Plus::class, $this->p($node->var) . ' += ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_assign_op_minus(Assign_Op\Minus $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Assign_Op\Minus::class, $this->p($node->var) . ' -= ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_assign_op_mul(Assign_Op\Mul $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Assign_Op\Mul::class, $this->p($node->var) . ' *= ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_assign_op_div(Assign_Op\Div $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Assign_Op\Div::class, $this->p($node->var) . ' /= ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_assign_op_concat(Assign_Op\Concat $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Assign_Op\Concat::class, $this->p($node->var) . ' .= ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_assign_op_mod(Assign_Op\Mod $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Assign_Op\Mod::class, $this->p($node->var) . ' %= ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_assign_op_bitwise_and(Assign_Op\Bitwise_And $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Assign_Op\Bitwise_And::class, $this->p($node->var) . ' &= ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_assign_op_bitwise_or(Assign_Op\Bitwise_Or $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Assign_Op\Bitwise_Or::class, $this->p($node->var) . ' |= ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_assign_op_bitwise_xor(Assign_Op\Bitwise_Xor $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Assign_Op\Bitwise_Xor::class, $this->p($node->var) . ' ^= ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_assign_op_shift_left(Assign_Op\Shift_Left $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Assign_Op\Shift_Left::class, $this->p($node->var) . ' <<= ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_assign_op_shift_right(Assign_Op\Shift_Right $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Assign_Op\Shift_Right::class, $this->p($node->var) . ' >>= ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_assign_op_pow(Assign_Op\Pow $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Assign_Op\Pow::class, $this->p($node->var) . ' **= ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_assign_op_coalesce(Assign_Op\Coalesce $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Assign_Op\Coalesce::class, $this->p($node->var) . ' ??= ', $node->expr, $precedence, $lhs_precedence);
    }
    // Binary expressions
    protected function p_expr_binary_op_plus(Binary_Op\Plus $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Plus::class, $node->left, ' + ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_minus(Binary_Op\Minus $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Minus::class, $node->left, ' - ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_mul(Binary_Op\Mul $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Mul::class, $node->left, ' * ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_div(Binary_Op\Div $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Div::class, $node->left, ' / ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_concat(Binary_Op\Concat $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Concat::class, $node->left, ' . ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_mod(Binary_Op\Mod $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Mod::class, $node->left, ' % ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_boolean_and(Binary_Op\Boolean_And $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Boolean_And::class, $node->left, ' && ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_boolean_or(Binary_Op\Boolean_Or $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Boolean_Or::class, $node->left, ' || ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_bitwise_and(Binary_Op\Bitwise_And $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Bitwise_And::class, $node->left, ' & ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_bitwise_or(Binary_Op\Bitwise_Or $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Bitwise_Or::class, $node->left, ' | ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_bitwise_xor(Binary_Op\Bitwise_Xor $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Bitwise_Xor::class, $node->left, ' ^ ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_shift_left(Binary_Op\Shift_Left $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Shift_Left::class, $node->left, ' << ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_shift_right(Binary_Op\Shift_Right $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Shift_Right::class, $node->left, ' >> ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_pow(Binary_Op\Pow $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Pow::class, $node->left, ' ** ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_logical_and(Binary_Op\Logical_And $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Logical_And::class, $node->left, ' and ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_logical_or(Binary_Op\Logical_Or $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Logical_Or::class, $node->left, ' or ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_logical_xor(Binary_Op\Logical_Xor $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Logical_Xor::class, $node->left, ' xor ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_equal(Binary_Op\Equal $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Equal::class, $node->left, ' == ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_not_equal(Binary_Op\Not_Equal $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Not_Equal::class, $node->left, ' != ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_identical(Binary_Op\Identical $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Identical::class, $node->left, ' === ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_not_identical(Binary_Op\Not_Identical $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Not_Identical::class, $node->left, ' !== ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_spaceship(Binary_Op\Spaceship $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Spaceship::class, $node->left, ' <=> ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_greater(Binary_Op\Greater $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Greater::class, $node->left, ' > ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_greater_or_equal(Binary_Op\Greater_Or_Equal $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Greater_Or_Equal::class, $node->left, ' >= ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_smaller(Binary_Op\Smaller $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Smaller::class, $node->left, ' < ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_smaller_or_equal(Binary_Op\Smaller_Or_Equal $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Smaller_Or_Equal::class, $node->left, ' <= ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_coalesce(Binary_Op\Coalesce $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Binary_Op\Coalesce::class, $node->left, ' ?? ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_binary_op_pipe(Binary_Op\Pipe $node, int $precedence, int $lhs_precedence): string
    {
        if ($node->right instanceof Expr\Arrow_Function) {
            // Force parentheses around arrow functions.
            $lhs_precedence = $this->precedence_map[Expr\Arrow_Function::class][0];
        }
        return $this->p_infix_op(Binary_Op\Pipe::class, $node->left, ' |> ', $node->right, $precedence, $lhs_precedence);
    }
    protected function p_expr_instanceof(Expr\Instanceof_ $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_postfix_op(Expr\Instanceof_::class, $node->expr, ' instanceof ' . $this->p_new_operand($node->class), $precedence, $lhs_precedence);
    }
    // Unary expressions
    protected function p_expr_boolean_not(Expr\Boolean_Not $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Expr\Boolean_Not::class, '!', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_bitwise_not(Expr\Bitwise_Not $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Expr\Bitwise_Not::class, '~', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_unary_minus(Expr\Unary_Minus $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Expr\Unary_Minus::class, '-', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_unary_plus(Expr\Unary_Plus $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Expr\Unary_Plus::class, '+', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_pre_inc(Expr\Pre_Inc $node): string
    {
        return '++' . $this->p($node->var);
    }
    protected function p_expr_pre_dec(Expr\Pre_Dec $node): string
    {
        return '--' . $this->p($node->var);
    }
    protected function p_expr_post_inc(Expr\Post_Inc $node): string
    {
        return $this->p($node->var) . '++';
    }
    protected function p_expr_post_dec(Expr\Post_Dec $node): string
    {
        return $this->p($node->var) . '--';
    }
    protected function p_expr_error_suppress(Expr\Error_Suppress $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Expr\Error_Suppress::class, '@', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_yield_from(Expr\Yield_From $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Expr\Yield_From::class, 'yield from ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_print(Expr\Print_ $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Expr\Print_::class, 'print ', $node->expr, $precedence, $lhs_precedence);
    }
    // Casts
    protected function p_expr_cast_int(Cast\Int_ $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Cast\Int_::class, '(int) ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_cast_double(Cast\Double $node, int $precedence, int $lhs_precedence): string
    {
        $kind = $node->get_attribute('kind', Cast\Double::KIND_DOUBLE);
        if ($kind === Cast\Double::KIND_DOUBLE) {
            $cast = '(double)';
        } elseif ($kind === Cast\Double::KIND_FLOAT) {
            $cast = '(float)';
        } else {
            assert($kind === Cast\Double::KIND_REAL);
            $cast = '(real)';
        }
        return $this->p_prefix_op(Cast\Double::class, $cast . ' ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_cast_string(Cast\String_ $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Cast\String_::class, '(string) ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_cast_array(Cast\Array_ $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Cast\Array_::class, '(array) ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_cast_object(Cast\Object_ $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Cast\Object_::class, '(object) ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_cast_bool(Cast\Bool_ $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Cast\Bool_::class, '(bool) ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_cast_unset(Cast\Unset_ $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Cast\Unset_::class, '(unset) ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_cast_void(Cast\Void_ $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Cast\Void_::class, '(void) ', $node->expr, $precedence, $lhs_precedence);
    }
    // Function calls and similar constructs
    protected function p_expr_func_call(Expr\Func_Call $node): string
    {
        return $this->p_call_lhs($node->name) . '(' . $this->p_maybe_multiline($node->args) . ')';
    }
    protected function p_expr_method_call(Expr\Method_Call $node): string
    {
        return $this->p_dereference_lhs($node->var) . '->' . $this->p_object_property($node->name) . '(' . $this->p_maybe_multiline($node->args) . ')';
    }
    protected function p_expr_nullsafe_method_call(Expr\Nullsafe_Method_Call $node): string
    {
        return $this->p_dereference_lhs($node->var) . '?->' . $this->p_object_property($node->name) . '(' . $this->p_maybe_multiline($node->args) . ')';
    }
    protected function p_expr_static_call(Expr\Static_Call $node): string
    {
        return $this->p_static_dereference_lhs($node->class) . '::' . ($node->name instanceof Expr ? $node->name instanceof Expr\Variable ? $this->p($node->name) : '{' . $this->p($node->name) . '}' : $node->name) . '(' . $this->p_maybe_multiline($node->args) . ')';
    }
    protected function p_expr_empty(Expr\Empty_ $node): string
    {
        return 'empty(' . $this->p($node->expr) . ')';
    }
    protected function p_expr_isset(Expr\Isset_ $node): string
    {
        return 'isset(' . $this->p_comma_separated($node->vars) . ')';
    }
    protected function p_expr_eval(Expr\Eval_ $node): string
    {
        return 'eval(' . $this->p($node->expr) . ')';
    }
    protected function p_expr_include(Expr\Include_ $node, int $precedence, int $lhs_precedence): string
    {
        static $map = [Expr\Include_::TYPE_INCLUDE => 'include', Expr\Include_::TYPE_INCLUDE_ONCE => 'include_once', Expr\Include_::TYPE_REQUIRE => 'require', Expr\Include_::TYPE_REQUIRE_ONCE => 'require_once'];
        return $this->p_prefix_op(Expr\Include_::class, $map[$node->type] . ' ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_list(Expr\List_ $node): string
    {
        $syntax = $node->get_attribute('kind', $this->php_version->supports_short_array_destructuring() ? Expr\List_::KIND_ARRAY : Expr\List_::KIND_LIST);
        if ($syntax === Expr\List_::KIND_ARRAY) {
            return '[' . $this->p_maybe_multiline($node->items, true) . ']';
        }
        return 'list(' . $this->p_maybe_multiline($node->items, true) . ')';
    }
    // Other
    protected function p_expr_error(Expr\Error $node): string
    {
        throw new \LogicException('Cannot pretty-print AST with Error nodes');
    }
    protected function p_expr_variable(Expr\Variable $node): string
    {
        if ($node->name instanceof Expr) {
            return '${' . $this->p($node->name) . '}';
        }
        return '$' . $node->name;
    }
    protected function p_expr_array(Expr\Array_ $node): string
    {
        $syntax = $node->get_attribute('kind', $this->short_array_syntax ? Expr\Array_::KIND_SHORT : Expr\Array_::KIND_LONG);
        if ($syntax === Expr\Array_::KIND_SHORT) {
            return '[' . $this->p_maybe_multiline($node->items, true) . ']';
        }
        return 'array(' . $this->p_maybe_multiline($node->items, true) . ')';
    }
    protected function p_key(?Node $node): string
    {
        if ($node === null) {
            return '';
        }
        // => is not really an operator and does not typically participate in precedence resolution.
        // However, there is an exception if yield expressions with keys are involved:
        // [yield $a => $b] is interpreted as [(yield $a => $b)], so we need to ensure that
        // [(yield $a) => $b] is printed with parentheses. We approximate this by lowering the LHS
        // precedence to that of yield (which will also print unnecessary parentheses for rare low
        // precedence unary operators like include).
        $yield_precedence = $this->precedence_map[Expr\Yield_::class][0];
        return $this->p($node, self::MAX_PRECEDENCE, $yield_precedence) . ' => ';
    }
    protected function p_array_item(Node\Array_Item $node): string
    {
        return $this->p_key($node->key) . ($node->by_ref ? '&' : '') . ($node->unpack ? '...' : '') . $this->p($node->value);
    }
    protected function p_expr_array_dim_fetch(Expr\Array_Dim_Fetch $node): string
    {
        return $this->p_dereference_lhs($node->var) . '[' . (null !== $node->dim ? $this->p($node->dim) : '') . ']';
    }
    protected function p_expr_const_fetch(Expr\Const_Fetch $node): string
    {
        return $this->p($node->name);
    }
    protected function p_expr_class_const_fetch(Expr\Class_Const_Fetch $node): string
    {
        return $this->p_static_dereference_lhs($node->class) . '::' . $this->p_object_property($node->name);
    }
    protected function p_expr_property_fetch(Expr\Property_Fetch $node): string
    {
        return $this->p_dereference_lhs($node->var) . '->' . $this->p_object_property($node->name);
    }
    protected function p_expr_nullsafe_property_fetch(Expr\Nullsafe_Property_Fetch $node): string
    {
        return $this->p_dereference_lhs($node->var) . '?->' . $this->p_object_property($node->name);
    }
    protected function p_expr_static_property_fetch(Expr\Static_Property_Fetch $node): string
    {
        return $this->p_static_dereference_lhs($node->class) . '::$' . $this->p_object_property($node->name);
    }
    protected function p_expr_shell_exec(Expr\Shell_Exec $node): string
    {
        return '`' . $this->p_encaps_list($node->parts, '`') . '`';
    }
    protected function p_expr_closure(Expr\Closure $node): string
    {
        return $this->p_attr_groups($node->attr_groups, true) . $this->p_static($node->static) . 'function ' . ($node->by_ref ? '&' : '') . '(' . $this->p_params($node->params) . ')' . (!empty($node->uses) ? ' use (' . $this->p_comma_separated($node->uses) . ')' : '') . (null !== $node->return_type ? ': ' . $this->p($node->return_type) : '') . ' {' . $this->p_stmts($node->stmts) . $this->nl . '}';
    }
    protected function p_expr_match(Expr\Match_ $node): string
    {
        return 'match (' . $this->p($node->cond) . ') {' . $this->p_comma_separated_multiline($node->arms, true) . $this->nl . '}';
    }
    protected function p_match_arm(Node\Match_Arm $node): string
    {
        $result = '';
        if ($node->conds) {
            for ($i = 0, $c = \count($node->conds); $i + 1 < $c; $i++) {
                $result .= $this->p($node->conds[$i]) . ', ';
            }
            $result .= $this->p_key($node->conds[$i]);
        } else {
            $result = 'default => ';
        }
        return $result . $this->p($node->body);
    }
    protected function p_expr_arrow_function(Expr\Arrow_Function $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Expr\Arrow_Function::class, $this->p_attr_groups($node->attr_groups, true) . $this->p_static($node->static) . 'fn' . ($node->by_ref ? '&' : '') . '(' . $this->p_params($node->params) . ')' . (null !== $node->return_type ? ': ' . $this->p($node->return_type) : '') . ' => ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_closure_use(Node\Closure_Use $node): string
    {
        return ($node->by_ref ? '&' : '') . $this->p($node->var);
    }
    protected function p_expr_new(Expr\New_ $node): string
    {
        if ($node->class instanceof Stmt\Class_) {
            $args = $node->args ? '(' . $this->p_maybe_multiline($node->args) . ')' : '';
            return 'new ' . $this->p_class_common($node->class, $args);
        }
        return 'new ' . $this->p_new_operand($node->class) . '(' . $this->p_maybe_multiline($node->args) . ')';
    }
    protected function p_expr_clone(Expr\Clone_ $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Expr\Clone_::class, 'clone ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_ternary(Expr\Ternary $node, int $precedence, int $lhs_precedence): string
    {
        // a bit of cheating: we treat the ternary as a binary op where the ?...: part is the operator.
        // this is okay because the part between ? and : never needs parentheses.
        return $this->p_infix_op(Expr\Ternary::class, $node->cond, ' ?' . (null !== $node->if ? ' ' . $this->p($node->if) . ' ' : '') . ': ', $node->else, $precedence, $lhs_precedence);
    }
    protected function p_expr_exit(Expr\Exit_ $node): string
    {
        $kind = $node->get_attribute('kind', Expr\Exit_::KIND_DIE);
        return ($kind === Expr\Exit_::KIND_EXIT ? 'exit' : 'die') . (null !== $node->expr ? '(' . $this->p($node->expr) . ')' : '');
    }
    protected function p_expr_throw(Expr\Throw_ $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_prefix_op(Expr\Throw_::class, 'throw ', $node->expr, $precedence, $lhs_precedence);
    }
    protected function p_expr_yield(Expr\Yield_ $node, int $precedence, int $lhs_precedence): string
    {
        if ($node->value === null) {
            $op_precedence = $this->precedence_map[Expr\Yield_::class][0];
            return $op_precedence >= $lhs_precedence ? '(yield)' : 'yield';
        }
        if (!$this->php_version->supports_yield_without_parentheses()) {
            return '(yield ' . $this->p_key($node->key) . $this->p($node->value) . ')';
        }
        return $this->p_prefix_op(Expr\Yield_::class, 'yield ' . $this->p_key($node->key), $node->value, $precedence, $lhs_precedence);
    }
    // Declarations
    protected function p_stmt_namespace(Stmt\Namespace_ $node): string
    {
        if ($this->can_use_semicolon_namespaces) {
            return 'namespace ' . $this->p($node->name) . ';' . $this->nl . $this->p_stmts($node->stmts, false);
        }
        return 'namespace' . (null !== $node->name ? ' ' . $this->p($node->name) : '') . ' {' . $this->p_stmts($node->stmts) . $this->nl . '}';
    }
    protected function p_stmt_use(Stmt\Use_ $node): string
    {
        return 'use ' . $this->p_use_type($node->type) . $this->p_comma_separated($node->uses) . ';';
    }
    protected function p_stmt_group_use(Stmt\Group_Use $node): string
    {
        return 'use ' . $this->p_use_type($node->type) . $this->p_name($node->prefix) . '\{' . $this->p_comma_separated($node->uses) . '};';
    }
    protected function p_use_item(Node\Use_Item $node): string
    {
        return $this->p_use_type($node->type) . $this->p($node->name) . (null !== $node->alias ? ' as ' . $node->alias : '');
    }
    protected function p_use_type(int $type): string
    {
        return $type === Stmt\Use_::TYPE_FUNCTION ? 'function ' : ($type === Stmt\Use_::TYPE_CONSTANT ? 'const ' : '');
    }
    protected function p_stmt_interface(Stmt\Interface_ $node): string
    {
        return $this->p_attr_groups($node->attr_groups) . 'interface ' . $node->name . (!empty($node->extends) ? ' extends ' . $this->p_comma_separated($node->extends) : '') . $this->nl . '{' . $this->p_stmts($node->stmts) . $this->nl . '}';
    }
    protected function p_stmt_enum(Stmt\Enum_ $node): string
    {
        return $this->p_attr_groups($node->attr_groups) . 'enum ' . $node->name . ($node->scalar_type ? ' : ' . $this->p($node->scalar_type) : '') . (!empty($node->implements) ? ' implements ' . $this->p_comma_separated($node->implements) : '') . $this->nl . '{' . $this->p_stmts($node->stmts) . $this->nl . '}';
    }
    protected function p_stmt_class(Stmt\Class_ $node): string
    {
        return $this->p_class_common($node, ' ' . $node->name);
    }
    protected function p_stmt_trait(Stmt\Trait_ $node): string
    {
        return $this->p_attr_groups($node->attr_groups) . 'trait ' . $node->name . $this->nl . '{' . $this->p_stmts($node->stmts) . $this->nl . '}';
    }
    protected function p_stmt_enum_case(Stmt\Enum_Case $node): string
    {
        return $this->p_attr_groups($node->attr_groups) . 'case ' . $node->name . ($node->expr ? ' = ' . $this->p($node->expr) : '') . ';';
    }
    protected function p_stmt_trait_use(Stmt\Trait_Use $node): string
    {
        return 'use ' . $this->p_comma_separated($node->traits) . (empty($node->adaptations) ? ';' : ' {' . $this->p_stmts($node->adaptations) . $this->nl . '}');
    }
    protected function p_stmt_trait_use_adaptation_precedence(Stmt\Trait_Use_Adaptation\Precedence $node): string
    {
        return $this->p($node->trait) . '::' . $node->method . ' insteadof ' . $this->p_comma_separated($node->insteadof) . ';';
    }
    protected function p_stmt_trait_use_adaptation_alias(Stmt\Trait_Use_Adaptation\Alias $node): string
    {
        return (null !== $node->trait ? $this->p($node->trait) . '::' : '') . $node->method . ' as' . (null !== $node->new_modifier ? ' ' . rtrim($this->p_modifiers($node->new_modifier), ' ') : '') . (null !== $node->new_name ? ' ' . $node->new_name : '') . ';';
    }
    protected function p_stmt_property(Stmt\Property $node): string
    {
        return $this->p_attr_groups($node->attr_groups) . (0 === $node->flags ? 'var ' : $this->p_modifiers($node->flags)) . ($node->type ? $this->p($node->type) . ' ' : '') . $this->p_comma_separated($node->props) . ($node->hooks ? ' {' . $this->p_stmts($node->hooks) . $this->nl . '}' : ';');
    }
    protected function p_property_item(Node\Property_Item $node): string
    {
        return '$' . $node->name . (null !== $node->default ? ' = ' . $this->p($node->default) : '');
    }
    protected function p_property_hook(Node\Property_Hook $node): string
    {
        return $this->p_attr_groups($node->attr_groups) . $this->p_modifiers($node->flags) . ($node->by_ref ? '&' : '') . $node->name . ($node->params ? '(' . $this->p_params($node->params) . ')' : '') . (\is_array($node->body) ? ' {' . $this->p_stmts($node->body) . $this->nl . '}' : ($node->body !== null ? ' => ' . $this->p($node->body) : '') . ';');
    }
    protected function p_stmt_class_method(Stmt\Class_Method $node): string
    {
        return $this->p_attr_groups($node->attr_groups) . $this->p_modifiers($node->flags) . 'function ' . ($node->by_ref ? '&' : '') . $node->name . '(' . $this->p_params($node->params) . ')' . (null !== $node->return_type ? ': ' . $this->p($node->return_type) : '') . (null !== $node->stmts ? $this->nl . '{' . $this->p_stmts($node->stmts) . $this->nl . '}' : ';');
    }
    protected function p_stmt_class_const(Stmt\Class_Const $node): string
    {
        return $this->p_attr_groups($node->attr_groups) . $this->p_modifiers($node->flags) . 'const ' . (null !== $node->type ? $this->p($node->type) . ' ' : '') . $this->p_comma_separated($node->consts) . ';';
    }
    protected function p_stmt_function(Stmt\Function_ $node): string
    {
        return $this->p_attr_groups($node->attr_groups) . 'function ' . ($node->by_ref ? '&' : '') . $node->name . '(' . $this->p_params($node->params) . ')' . (null !== $node->return_type ? ': ' . $this->p($node->return_type) : '') . $this->nl . '{' . $this->p_stmts($node->stmts) . $this->nl . '}';
    }
    protected function p_stmt_const(Stmt\Const_ $node): string
    {
        return $this->p_attr_groups($node->attr_groups) . 'const ' . $this->p_comma_separated($node->consts) . ';';
    }
    protected function p_stmt_declare(Stmt\Declare_ $node): string
    {
        return 'declare (' . $this->p_comma_separated($node->declares) . ')' . (null !== $node->stmts ? ' {' . $this->p_stmts($node->stmts) . $this->nl . '}' : ';');
    }
    protected function p_declare_item(Node\Declare_Item $node): string
    {
        return $node->key . '=' . $this->p($node->value);
    }
    // Control flow
    protected function p_stmt_if(Stmt\If_ $node): string
    {
        return 'if (' . $this->p($node->cond) . ') {' . $this->p_stmts($node->stmts) . $this->nl . '}' . ($node->elseifs ? ' ' . $this->p_implode($node->elseifs, ' ') : '') . (null !== $node->else ? ' ' . $this->p($node->else) : '');
    }
    protected function p_stmt_else_if(Stmt\Else_If_ $node): string
    {
        return 'elseif (' . $this->p($node->cond) . ') {' . $this->p_stmts($node->stmts) . $this->nl . '}';
    }
    protected function p_stmt_else(Stmt\Else_ $node): string
    {
        if (\count($node->stmts) === 1 && $node->stmts[0] instanceof Stmt\If_) {
            // Print as "else if" rather than "else { if }"
            return 'else ' . $this->p($node->stmts[0]);
        }
        return 'else {' . $this->p_stmts($node->stmts) . $this->nl . '}';
    }
    protected function p_stmt_for(Stmt\For_ $node): string
    {
        return 'for (' . $this->p_comma_separated($node->init) . ';' . (!empty($node->cond) ? ' ' : '') . $this->p_comma_separated($node->cond) . ';' . (!empty($node->loop) ? ' ' : '') . $this->p_comma_separated($node->loop) . ') {' . $this->p_stmts($node->stmts) . $this->nl . '}';
    }
    protected function p_stmt_foreach(Stmt\Foreach_ $node): string
    {
        return 'foreach (' . $this->p($node->expr) . ' as ' . (null !== $node->key_var ? $this->p($node->key_var) . ' => ' : '') . ($node->by_ref ? '&' : '') . $this->p($node->value_var) . ') {' . $this->p_stmts($node->stmts) . $this->nl . '}';
    }
    protected function p_stmt_while(Stmt\While_ $node): string
    {
        return 'while (' . $this->p($node->cond) . ') {' . $this->p_stmts($node->stmts) . $this->nl . '}';
    }
    protected function p_stmt_do(Stmt\Do_ $node): string
    {
        return 'do {' . $this->p_stmts($node->stmts) . $this->nl . '} while (' . $this->p($node->cond) . ');';
    }
    protected function p_stmt_switch(Stmt\Switch_ $node): string
    {
        return 'switch (' . $this->p($node->cond) . ') {' . $this->p_stmts($node->cases) . $this->nl . '}';
    }
    protected function p_stmt_case(Stmt\Case_ $node): string
    {
        return (null !== $node->cond ? 'case ' . $this->p($node->cond) : 'default') . ':' . $this->p_stmts($node->stmts);
    }
    protected function p_stmt_try_catch(Stmt\Try_Catch $node): string
    {
        return 'try {' . $this->p_stmts($node->stmts) . $this->nl . '}' . ($node->catches ? ' ' . $this->p_implode($node->catches, ' ') : '') . ($node->finally !== null ? ' ' . $this->p($node->finally) : '');
    }
    protected function p_stmt_catch(Stmt\Catch_ $node): string
    {
        return 'catch (' . $this->p_implode($node->types, '|') . ($node->var !== null ? ' ' . $this->p($node->var) : '') . ') {' . $this->p_stmts($node->stmts) . $this->nl . '}';
    }
    protected function p_stmt_finally(Stmt\Finally_ $node): string
    {
        return 'finally {' . $this->p_stmts($node->stmts) . $this->nl . '}';
    }
    protected function p_stmt_break(Stmt\Break_ $node): string
    {
        return 'break' . ($node->num !== null ? ' ' . $this->p($node->num) : '') . ';';
    }
    protected function p_stmt_continue(Stmt\Continue_ $node): string
    {
        return 'continue' . ($node->num !== null ? ' ' . $this->p($node->num) : '') . ';';
    }
    protected function p_stmt_return(Stmt\Return_ $node): string
    {
        return 'return' . (null !== $node->expr ? ' ' . $this->p($node->expr) : '') . ';';
    }
    protected function p_stmt_label(Stmt\Label $node): string
    {
        return $node->name . ':';
    }
    protected function p_stmt_goto(Stmt\Goto_ $node): string
    {
        return 'goto ' . $node->name . ';';
    }
    // Other
    protected function p_stmt_expression(Stmt\Expression $node): string
    {
        return $this->p($node->expr) . ';';
    }
    protected function p_stmt_echo(Stmt\Echo_ $node): string
    {
        return 'echo ' . $this->p_comma_separated($node->exprs) . ';';
    }
    protected function p_stmt_static(Stmt\Static_ $node): string
    {
        return 'static ' . $this->p_comma_separated($node->vars) . ';';
    }
    protected function p_stmt_global(Stmt\Global_ $node): string
    {
        return 'global ' . $this->p_comma_separated($node->vars) . ';';
    }
    protected function p_static_var(Node\Static_Var $node): string
    {
        return $this->p($node->var) . (null !== $node->default ? ' = ' . $this->p($node->default) : '');
    }
    protected function p_stmt_unset(Stmt\Unset_ $node): string
    {
        return 'unset(' . $this->p_comma_separated($node->vars) . ');';
    }
    protected function p_stmt_inline_html(Stmt\Inline_Html $node): string
    {
        $newline = $node->get_attribute('hasLeadingNewline', true) ? $this->newline : '';
        return '?>' . $newline . $node->value . '<?php ';
    }
    protected function p_stmt_halt_compiler(Stmt\Halt_Compiler $node): string
    {
        return '__halt_compiler();' . $node->remaining;
    }
    protected function p_stmt_nop(Stmt\Nop $node): string
    {
        return '';
    }
    protected function p_stmt_block(Stmt\Block $node): string
    {
        return '{' . $this->p_stmts($node->stmts) . $this->nl . '}';
    }
    // Helpers
    protected function p_class_common(Stmt\Class_ $node, string $after_class_token): string
    {
        return $this->p_attr_groups($node->attr_groups, $node->name === null) . $this->p_modifiers($node->flags) . 'class' . $after_class_token . (null !== $node->extends ? ' extends ' . $this->p($node->extends) : '') . (!empty($node->implements) ? ' implements ' . $this->p_comma_separated($node->implements) : '') . $this->nl . '{' . $this->p_stmts($node->stmts) . $this->nl . '}';
    }
    protected function p_object_property(Node $node): string
    {
        if ($node instanceof Expr) {
            return '{' . $this->p($node) . '}';
        }
        assert($node instanceof Node\Identifier);
        return $node->name;
    }
    /** @param (Expr|Node\InterpolatedStringPart)[] $encapsList */
    protected function p_encaps_list(array $encaps_list, ?string $quote): string
    {
        $return = '';
        foreach ($encaps_list as $element) {
            if ($element instanceof Node\Interpolated_String_Part) {
                $return .= $this->escape_string($element->value, $quote);
            } else {
                $return .= '{' . $this->p($element) . '}';
            }
        }
        return $return;
    }
    protected function p_single_quoted_string(string $string): string
    {
        // It is idiomatic to only escape backslashes when necessary, i.e. when followed by ', \ or
        // the end of the string ('Foo\Bar' instead of 'Foo\\Bar'). However, we also don't want to
        // produce an odd number of backslashes, so '\\\\a' should not get rendered as '\\\a', even
        // though that would be legal.
        $regex = '/\'|\\\\(?=[\'\\\\]|$)|(?<=\\\\)\\\\/';
        return '\'' . preg_replace($regex, '\\\\$0', $string) . '\'';
    }
    protected function escape_string(string $string, ?string $quote): string
    {
        if (null === $quote) {
            // For doc strings, don't escape newlines
            $escaped = addcslashes($string, "\t\f\v\$\\");
            // But do escape isolated \r. Combined with the terminating newline, it might get
            // interpreted as \r\n and dropped from the string contents.
            $escaped = preg_replace('/\r(?!\n)/', '\r', $escaped);
            if ($this->php_version->supports_flexible_heredoc()) {
                $escaped = $this->indent_string($escaped);
            }
        } else {
            $escaped = addcslashes($string, "\n\r\t\f\v\$" . $quote . '\\');
        }
        // Escape control characters and non-UTF-8 characters.
        // Regex based on https://stackoverflow.com/a/11709412/385378.
        $regex = '/(
              [\x00-\x08\x0E-\x1F] # Control characters
            | [\xC0-\xC1] # Invalid UTF-8 Bytes
            | [\xF5-\xFF] # Invalid UTF-8 Bytes
            | \xE0(?=[\x80-\x9F]) # Overlong encoding of prior code point
            | \xF0(?=[\x80-\x8F]) # Overlong encoding of prior code point
            | [\xC2-\xDF](?![\x80-\xBF]) # Invalid UTF-8 Sequence Start
            | [\xE0-\xEF](?![\x80-\xBF]{2}) # Invalid UTF-8 Sequence Start
            | [\xF0-\xF4](?![\x80-\xBF]{3}) # Invalid UTF-8 Sequence Start
            | (?<=[\x00-\x7F\xF5-\xFF])[\x80-\xBF] # Invalid UTF-8 Sequence Middle
            | (?<![\xC2-\xDF]|[\xE0-\xEF]|[\xE0-\xEF][\x80-\xBF]|[\xF0-\xF4]|[\xF0-\xF4][\x80-\xBF]|[\xF0-\xF4][\x80-\xBF]{2})[\x80-\xBF] # Overlong Sequence
            | (?<=[\xE0-\xEF])[\x80-\xBF](?![\x80-\xBF]) # Short 3 byte sequence
            | (?<=[\xF0-\xF4])[\x80-\xBF](?![\x80-\xBF]{2}) # Short 4 byte sequence
            | (?<=[\xF0-\xF4][\x80-\xBF])[\x80-\xBF](?![\x80-\xBF]) # Short 4 byte sequence (2)
        )/x';
        return preg_replace_callback($regex, function (array $matches): string {
            assert(strlen($matches[0]) === 1);
            $hex = dechex(ord($matches[0]));
            return '\x' . str_pad($hex, 2, '0', \STR_PAD_LEFT);
        }, $escaped);
    }
    protected function contains_end_label(string $string, string $label, bool $at_start = true): bool
    {
        $start = $at_start ? '(?:^|[\r\n])[ \t]*' : '[\r\n][ \t]*';
        return false !== strpos($string, $label) && preg_match('/' . $start . $label . '(?:$|[^_A-Za-z0-9\x80-\xff])/', $string);
    }
    /** @param (Expr|Node\InterpolatedStringPart)[] $parts */
    protected function encapsed_contains_end_label(array $parts, string $label): bool
    {
        foreach ($parts as $i => $part) {
            if ($part instanceof Node\Interpolated_String_Part && $this->contains_end_label($this->escape_string($part->value, null), $label, $i === 0)) {
                return true;
            }
        }
        return false;
    }
    protected function p_dereference_lhs(Node $node): string
    {
        if (!$this->dereference_lhs_requires_parens($node)) {
            return $this->p($node);
        }
        return '(' . $this->p($node) . ')';
    }
    protected function p_static_dereference_lhs(Node $node): string
    {
        if (!$this->static_dereference_lhs_requires_parens($node)) {
            return $this->p($node);
        }
        return '(' . $this->p($node) . ')';
    }
    protected function p_call_lhs(Node $node): string
    {
        if (!$this->call_lhs_requires_parens($node)) {
            return $this->p($node);
        }
        return '(' . $this->p($node) . ')';
    }
    protected function p_new_operand(Node $node): string
    {
        if (!$this->new_operand_requires_parens($node)) {
            return $this->p($node);
        }
        return '(' . $this->p($node) . ')';
    }
    /**
     * @param Node[] $nodes
     */
    protected function has_node_with_comments(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if ($node && $node->get_comments()) {
                return true;
            }
        }
        return false;
    }
    /** @param Node[] $nodes */
    protected function p_maybe_multiline(array $nodes, bool $trailing_comma = false): string
    {
        if (!$this->has_node_with_comments($nodes)) {
            return $this->p_comma_separated($nodes);
        }
        return $this->p_comma_separated_multiline($nodes, $trailing_comma) . $this->nl;
    }
    /** @param Node\Param[] $params
     */
    private function has_param_with_attributes(array $params): bool
    {
        foreach ($params as $param) {
            if ($param->attr_groups) {
                return true;
            }
        }
        return false;
    }
    /** @param Node\Param[] $params */
    protected function p_params(array $params): string
    {
        if ($this->has_node_with_comments($params) || $this->has_param_with_attributes($params) && !$this->php_version->supports_attributes()) {
            return $this->p_comma_separated_multiline($params, $this->php_version->supports_trailing_comma_in_param_list()) . $this->nl;
        }
        return $this->p_comma_separated($params);
    }
    /** @param Node\AttributeGroup[] $nodes */
    protected function p_attr_groups(array $nodes, bool $inline = false): string
    {
        $result = '';
        $sep = $inline ? ' ' : $this->nl;
        foreach ($nodes as $node) {
            $result .= $this->p($node) . $sep;
        }
        return $result;
    }
}