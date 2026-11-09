<?php

declare (strict_types=1);
namespace Php_Parser;

/**
 * Thrown when a constant expression cannot be evaluated.
 *
 * This exception is raised by {@see ConstExprEvaluator} when:
 *  - A node type is encountered that the evaluator does not handle and no fallback was provided.
 *  - A PHP error, warning, or notice occurs during evaluation (only from evaluateSilently()).
 *  - A user-supplied fallback evaluator throws this exception explicitly.
 *
 * The previous exception (accessible via getPrevious()) provides the original PHP error
 * that triggered the exception when evaluating silently.
 *
 * @see ConstExprEvaluator::evaluateSilently()
 * @see ConstExprEvaluator::evaluateDirectly()
 */
class Const_Expr_Evaluation_Exception extends \RuntimeException
{
}