<?php

declare (strict_types=1);
namespace Php_Parser;

use function array_merge;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Scalar;
/**
 * Evaluates constant expressions.
 *
 * This evaluator is able to evaluate all constant expressions (as defined by PHP), which can be
 * evaluated without further context. If a subexpression is not of this type, a user-provided
 * fallback evaluator is invoked. To support all constant expressions that are also supported by
 * PHP (and not already handled by this class), the fallback evaluator must be able to handle the
 * following node types:
 *
 *  * All Scalar\MagicConst\* nodes.
 *  * Expr\ConstFetch nodes. Only null/false/true are already handled by this class.
 *  * Expr\ClassConstFetch nodes.
 *
 * The fallback evaluator should throw ConstExprEvaluationException for nodes it cannot evaluate.
 *
 * The evaluation is dependent on runtime configuration in two respects: Firstly, floating
 * point to string conversions are affected by the precision ini setting. Secondly, they are also
 * affected by the LC_NUMERIC locale.
 */
class Const_Expr_Evaluator
{
    /** @var callable|null */
    private $fallback_evaluator;
    /**
     * Create a constant expression evaluator.
     *
     * The provided fallback evaluator is invoked whenever a subexpression cannot be evaluated. See
     * class doc comment for more information.
     *
     * @param callable|null $fallbackEvaluator To call if subexpression cannot be evaluated
     */
    public function __construct(?callable $fallback_evaluator = null)
    {
        $this->fallback_evaluator = $fallback_evaluator ?? function (Expr $expr): void {
            throw new Const_Expr_Evaluation_Exception("Expression of type {$expr->get_type()} cannot be evaluated");
        };
    }
    /**
     * Silently evaluates a constant expression into a PHP value.
     *
     * Thrown Errors, warnings or notices will be converted into a ConstExprEvaluationException.
     * The original source of the exception is available through getPrevious().
     *
     * If some part of the expression cannot be evaluated, the fallback evaluator passed to the
     * constructor will be invoked. By default, if no fallback is provided, an exception of type
     * ConstExprEvaluationException is thrown.
     *
     * See class doc comment for caveats and limitations.
     *
     * @param Expr $expr Constant expression to evaluate
     * @return mixed Result of evaluation
     *
     * @throws ConstExprEvaluationException if the expression cannot be evaluated or an error occurred
     */
    public function evaluate_silently(Expr $expr)
    {
        set_error_handler(function ($num, $str, $file, $line): void {
            throw new \ErrorException($str, 0, $num, $file, $line);
        });
        try {
            return $this->evaluate($expr);
        } catch (\Throwable $e) {
            if (!$e instanceof Const_Expr_Evaluation_Exception) {
                $e = new Const_Expr_Evaluation_Exception('An error occurred during constant expression evaluation', 0, $e);
            }
            throw $e;
        } finally {
            restore_error_handler();
        }
    }
    /**
     * Directly evaluates a constant expression into a PHP value.
     *
     * May generate Error exceptions, warnings or notices. Use evaluateSilently() to convert these
     * into a ConstExprEvaluationException.
     *
     * If some part of the expression cannot be evaluated, the fallback evaluator passed to the
     * constructor will be invoked. By default, if no fallback is provided, an exception of type
     * ConstExprEvaluationException is thrown.
     *
     * See class doc comment for caveats and limitations.
     *
     * @param Expr $expr Constant expression to evaluate
     * @return mixed Result of evaluation
     *
     * @throws ConstExprEvaluationException if the expression cannot be evaluated
     */
    public function evaluate_directly(Expr $expr)
    {
        return $this->evaluate($expr);
    }
    /** @return mixed */
    private function evaluate(Expr $expr)
    {
        if ($expr instanceof Scalar\Int_ || $expr instanceof Scalar\Float_ || $expr instanceof Scalar\String_) {
            return $expr->value;
        }
        if ($expr instanceof Expr\Array_) {
            return $this->evaluate_array($expr);
        }
        // Unary operators
        if ($expr instanceof Expr\Unary_Plus) {
            return +$this->evaluate($expr->expr);
        }
        if ($expr instanceof Expr\Unary_Minus) {
            return -$this->evaluate($expr->expr);
        }
        if ($expr instanceof Expr\Boolean_Not) {
            return !$this->evaluate($expr->expr);
        }
        if ($expr instanceof Expr\Bitwise_Not) {
            return ~$this->evaluate($expr->expr);
        }
        if ($expr instanceof Expr\Binary_Op) {
            return $this->evaluate_binary_op($expr);
        }
        if ($expr instanceof Expr\Ternary) {
            return $this->evaluate_ternary($expr);
        }
        if ($expr instanceof Expr\Array_Dim_Fetch && null !== $expr->dim) {
            return $this->evaluate($expr->var)[$this->evaluate($expr->dim)];
        }
        if ($expr instanceof Expr\Const_Fetch) {
            return $this->evaluate_const_fetch($expr);
        }
        return ($this->fallback_evaluator)($expr);
    }
    private function evaluate_array(Expr\Array_ $expr): array
    {
        $array = [];
        foreach ($expr->items as $item) {
            if (null !== $item->key) {
                $array[$this->evaluate($item->key)] = $this->evaluate($item->value);
            } elseif ($item->unpack) {
                $array = array_merge($array, $this->evaluate($item->value));
            } else {
                $array[] = $this->evaluate($item->value);
            }
        }
        return $array;
    }
    /** @return mixed */
    private function evaluate_ternary(Expr\Ternary $expr)
    {
        if (null === $expr->if) {
            return $this->evaluate($expr->cond) ?: $this->evaluate($expr->else);
        }
        return $this->evaluate($expr->cond) ? $this->evaluate($expr->if) : $this->evaluate($expr->else);
    }
    /** @return mixed */
    private function evaluate_binary_op(Expr\Binary_Op $expr)
    {
        if ($expr instanceof Expr\Binary_Op\Coalesce && $expr->left instanceof Expr\Array_Dim_Fetch) {
            // This needs to be special cased to respect BP_VAR_IS fetch semantics
            return $this->evaluate($expr->left->var)[$this->evaluate($expr->left->dim)] ?? $this->evaluate($expr->right);
        }
        // The evaluate() calls are repeated in each branch, because some of the operators are
        // short-circuiting and evaluating the RHS in advance may be illegal in that case
        $l = $expr->left;
        $r = $expr->right;
        switch ($expr->get_operator_sigil()) {
            case '&':
                return $this->evaluate($l) & $this->evaluate($r);
            case '|':
                return $this->evaluate($l) | $this->evaluate($r);
            case '^':
                return $this->evaluate($l) ^ $this->evaluate($r);
            case '&&':
                return $this->evaluate($l) && $this->evaluate($r);
            case '||':
                if ($this->evaluate($l)) {
                    return true;
                }
                return (bool) $this->evaluate($r);
            case '??':
                return $this->evaluate($l) ?? $this->evaluate($r);
            case '.':
                return $this->evaluate($l) . $this->evaluate($r);
            case '/':
                return $this->evaluate($l) / $this->evaluate($r);
            case '==':
                return $this->evaluate($l) == $this->evaluate($r);
            case '>':
                return $this->evaluate($l) > $this->evaluate($r);
            case '>=':
                return $this->evaluate($l) >= $this->evaluate($r);
            case '===':
                return $this->evaluate($l) === $this->evaluate($r);
            case 'and':
                return $this->evaluate($l) and $this->evaluate($r);
            case 'or':
                return $this->evaluate($l) or $this->evaluate($r);
            case 'xor':
                return $this->evaluate($l) xor $this->evaluate($r);
            case '-':
                return $this->evaluate($l) - $this->evaluate($r);
            case '%':
                return $this->evaluate($l) % $this->evaluate($r);
            case '*':
                return $this->evaluate($l) * $this->evaluate($r);
            case '!=':
                return $this->evaluate($l) != $this->evaluate($r);
            case '!==':
                return $this->evaluate($l) !== $this->evaluate($r);
            case '+':
                return $this->evaluate($l) + $this->evaluate($r);
            case '**':
                return $this->evaluate($l) ** $this->evaluate($r);
            case '<<':
                return $this->evaluate($l) << $this->evaluate($r);
            case '>>':
                return $this->evaluate($l) >> $this->evaluate($r);
            case '<':
                return $this->evaluate($l) < $this->evaluate($r);
            case '<=':
                return $this->evaluate($l) <= $this->evaluate($r);
            case '<=>':
                return $this->evaluate($l) <=> $this->evaluate($r);
            case '|>':
                $lval = $this->evaluate($l);
                return $this->evaluate($r)($lval);
        }
        throw new \Exception('Should not happen');
    }
    /** @return mixed */
    private function evaluate_const_fetch(Expr\Const_Fetch $expr)
    {
        $name = $expr->name->to_lower_string();
        switch ($name) {
            case 'null':
                return null;
            case 'false':
                return false;
            case 'true':
                return true;
        }
        return ($this->fallback_evaluator)($expr);
    }
}