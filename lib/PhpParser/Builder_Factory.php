<?php

declare (strict_types=1);
namespace Php_Parser;

use Php_Parser\Node\Arg;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Binary_Op\Concat;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Scalar\String_;
use Php_Parser\Node\Stmt\Use_;
class Builder_Factory
{
    /**
     * Creates an attribute node.
     *
     * @param string|Name $name Name of the attribute
     * @param array $args Attribute named arguments
     */
    public function attribute($name, array $args = []): Node\Attribute
    {
        return new Node\Attribute(Builder_Helpers::normalize_name($name), $this->args($args));
    }
    /**
     * Creates a namespace builder.
     *
     * @param null|string|Node\Name $name Name of the namespace
     *
     * @return Builder\Namespace_ The created namespace builder
     */
    public function namespace($name): Builder\Namespace_
    {
        return new Builder\Namespace_($name);
    }
    /**
     * Creates a class builder.
     *
     * @param string $name Name of the class
     *
     * @return Builder\Class_ The created class builder
     */
    public function class(string $name): Builder\Class_
    {
        return new Builder\Class_($name);
    }
    /**
     * Creates an interface builder.
     *
     * @param string $name Name of the interface
     *
     * @return Builder\Interface_ The created interface builder
     */
    public function interface(string $name): Builder\Interface_
    {
        return new Builder\Interface_($name);
    }
    /**
     * Creates a trait builder.
     *
     * @param string $name Name of the trait
     *
     * @return Builder\Trait_ The created trait builder
     */
    public function trait(string $name): Builder\Trait_
    {
        return new Builder\Trait_($name);
    }
    /**
     * Creates an enum builder.
     *
     * @param string $name Name of the enum
     *
     * @return Builder\Enum_ The created enum builder
     */
    public function enum(string $name): Builder\Enum_
    {
        return new Builder\Enum_($name);
    }
    /**
     * Creates a trait use builder.
     *
     * @param Node\Name|string ...$traits Trait names
     *
     * @return Builder\TraitUse The created trait use builder
     */
    public function use_trait(...$traits): Builder\Trait_Use
    {
        return new Builder\Trait_Use(...$traits);
    }
    /**
     * Creates a trait use adaptation builder.
     *
     * @param Node\Name|string|null $trait Trait name
     * @param Node\Identifier|string $method Method name
     *
     * @return Builder\TraitUseAdaptation The created trait use adaptation builder
     */
    public function trait_use_adaptation($trait, $method = null): Builder\Trait_Use_Adaptation
    {
        if ($method === null) {
            $method = $trait;
            $trait = null;
        }
        return new Builder\Trait_Use_Adaptation($trait, $method);
    }
    /**
     * Creates a method builder.
     *
     * @param string $name Name of the method
     *
     * @return Builder\Method The created method builder
     */
    public function method(string $name): Builder\Method
    {
        return new Builder\Method($name);
    }
    /**
     * Creates a parameter builder.
     *
     * @param string $name Name of the parameter
     *
     * @return Builder\Param The created parameter builder
     */
    public function param(string $name): Builder\Param
    {
        return new Builder\Param($name);
    }
    /**
     * Creates a property builder.
     *
     * @param string $name Name of the property
     *
     * @return Builder\Property The created property builder
     */
    public function property(string $name): Builder\Property
    {
        return new Builder\Property($name);
    }
    /**
     * Creates a function builder.
     *
     * @param string $name Name of the function
     *
     * @return Builder\Function_ The created function builder
     */
    public function function(string $name): Builder\Function_
    {
        return new Builder\Function_($name);
    }
    /**
     * Creates a namespace/class use builder.
     *
     * @param Node\Name|string $name Name of the entity (namespace or class) to alias
     *
     * @return Builder\Use_ The created use builder
     */
    public function use($name): Builder\Use_
    {
        return new Builder\Use_($name, Use_::TYPE_NORMAL);
    }
    /**
     * Creates a function use builder.
     *
     * @param Node\Name|string $name Name of the function to alias
     *
     * @return Builder\Use_ The created use function builder
     */
    public function use_function($name): Builder\Use_
    {
        return new Builder\Use_($name, Use_::TYPE_FUNCTION);
    }
    /**
     * Creates a constant use builder.
     *
     * @param Node\Name|string $name Name of the const to alias
     *
     * @return Builder\Use_ The created use const builder
     */
    public function use_const($name): Builder\Use_
    {
        return new Builder\Use_($name, Use_::TYPE_CONSTANT);
    }
    /**
     * Creates a class constant builder.
     *
     * @param string|Identifier $name Name
     * @param Node\Expr|bool|null|int|float|string|array $value Value
     *
     * @return Builder\ClassConst The created use const builder
     */
    public function class_const($name, $value): Builder\Class_Const
    {
        return new Builder\Class_Const($name, $value);
    }
    /**
     * Creates an enum case builder.
     *
     * @param string|Identifier $name Name
     *
     * @return Builder\EnumCase The created use const builder
     */
    public function enum_case($name): Builder\Enum_Case
    {
        return new Builder\Enum_Case($name);
    }
    /**
     * Creates node a for a literal value.
     *
     * @param Expr|bool|null|int|float|string|array|\UnitEnum $value $value
     */
    public function val($value): Expr
    {
        return Builder_Helpers::normalize_value($value);
    }
    /**
     * Creates variable node.
     *
     * @param string|Expr $name Name
     */
    public function var($name): Expr\Variable
    {
        if (!\is_string($name) && !$name instanceof Expr) {
            throw new \LogicException('Variable name must be string or Expr');
        }
        return new Expr\Variable($name);
    }
    /**
     * Normalizes an argument list.
     *
     * Creates Arg nodes for all arguments and converts literal values to expressions.
     *
     * @param array $args List of arguments to normalize
     *
     * @return list<Arg>
     */
    public function args(array $args): array
    {
        $normalized_args = [];
        foreach ($args as $key => $arg) {
            if (!$arg instanceof Arg) {
                $arg = new Arg(Builder_Helpers::normalize_value($arg));
            }
            if (\is_string($key)) {
                $arg->name = Builder_Helpers::normalize_identifier($key);
            }
            $normalized_args[] = $arg;
        }
        return $normalized_args;
    }
    /**
     * Creates a function call node.
     *
     * @param string|Name|Expr $name Function name
     * @param array $args Function arguments
     */
    public function func_call($name, array $args = []): Expr\Func_Call
    {
        return new Expr\Func_Call(Builder_Helpers::normalize_name_or_expr($name), $this->args($args));
    }
    /**
     * Creates a method call node.
     *
     * @param Expr $var Variable the method is called on
     * @param string|Identifier|Expr $name Method name
     * @param array $args Method arguments
     */
    public function method_call(Expr $var, $name, array $args = []): Expr\Method_Call
    {
        return new Expr\Method_Call($var, Builder_Helpers::normalize_identifier_or_expr($name), $this->args($args));
    }
    /**
     * Creates a static method call node.
     *
     * @param string|Name|Expr $class Class name
     * @param string|Identifier|Expr $name Method name
     * @param array $args Method arguments
     */
    public function static_call($class, $name, array $args = []): Expr\Static_Call
    {
        return new Expr\Static_Call(Builder_Helpers::normalize_name_or_expr($class), Builder_Helpers::normalize_identifier_or_expr($name), $this->args($args));
    }
    /**
     * Creates an object creation node.
     *
     * @param string|Name|Expr $class Class name
     * @param array $args Constructor arguments
     */
    public function new($class, array $args = []): Expr\New_
    {
        return new Expr\New_(Builder_Helpers::normalize_name_or_expr($class), $this->args($args));
    }
    /**
     * Creates a constant fetch node.
     *
     * @param string|Name $name Constant name
     */
    public function const_fetch($name): Expr\Const_Fetch
    {
        return new Expr\Const_Fetch(Builder_Helpers::normalize_name($name));
    }
    /**
     * Creates a property fetch node.
     *
     * @param Expr $var Variable holding object
     * @param string|Identifier|Expr $name Property name
     */
    public function property_fetch(Expr $var, $name): Expr\Property_Fetch
    {
        return new Expr\Property_Fetch($var, Builder_Helpers::normalize_identifier_or_expr($name));
    }
    /**
     * Creates a class constant fetch node.
     *
     * @param string|Name|Expr $class Class name
     * @param string|Identifier|Expr $name Constant name
     */
    public function class_const_fetch($class, $name): Expr\Class_Const_Fetch
    {
        return new Expr\Class_Const_Fetch(Builder_Helpers::normalize_name_or_expr($class), Builder_Helpers::normalize_identifier_or_expr($name));
    }
    /**
     * Creates nested Concat nodes from a list of expressions.
     *
     * @param Expr|string ...$exprs Expressions or literal strings
     */
    public function concat(...$exprs): Concat
    {
        $num_exprs = count($exprs);
        if ($num_exprs < 2) {
            throw new \LogicException('Expected at least two expressions');
        }
        $last_concat = $this->normalize_string_expr($exprs[0]);
        for ($i = 1; $i < $num_exprs; $i++) {
            $last_concat = new Concat($last_concat, $this->normalize_string_expr($exprs[$i]));
        }
        return $last_concat;
    }
    /**
     * @param string|Expr $expr
     */
    private function normalize_string_expr($expr): Expr
    {
        if ($expr instanceof Expr) {
            return $expr;
        }
        if (\is_string($expr)) {
            return new String_($expr);
        }
        throw new \LogicException('Expected string or Expr');
    }
}