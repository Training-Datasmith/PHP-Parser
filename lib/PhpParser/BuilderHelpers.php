<?php

declare (strict_types=1);
namespace Php_Parser;

use Php_Parser\Node\Complex_Type;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Parser\Node\Nullable_Type;
use Php_Parser\Node\Scalar;
use Php_Parser\Node\Stmt;
/**
 * This class defines helpers used in the implementation of builders. Don't use it directly.
 *
 * @internal
 */
final class Builder_Helpers
{
    /**
     * Normalizes a node: Converts builder objects to nodes.
     *
     * @param Node|Builder $node The node to normalize
     *
     * @return Node The normalized node
     */
    public static function normalize_node($node): Node
    {
        if ($node instanceof Builder) {
            return $node->get_node();
        }
        if ($node instanceof Node) {
            return $node;
        }
        throw new \LogicException('Expected node or builder object');
    }
    /**
     * Normalizes a node to a statement.
     *
     * Expressions are wrapped in a Stmt\Expression node.
     *
     * @param Node|Builder $node The node to normalize
     *
     * @return Stmt The normalized statement node
     */
    public static function normalize_stmt($node): Stmt
    {
        $node = self::normalize_node($node);
        if ($node instanceof Stmt) {
            return $node;
        }
        if ($node instanceof Expr) {
            return new Stmt\Expression($node);
        }
        throw new \LogicException('Expected statement or expression node');
    }
    /**
     * Normalizes strings to Identifier.
     *
     * @param string|Identifier $name The identifier to normalize
     *
     * @return Identifier The normalized identifier
     */
    public static function normalize_identifier($name): Identifier
    {
        if ($name instanceof Identifier) {
            return $name;
        }
        if (\is_string($name)) {
            return new Identifier($name);
        }
        throw new \LogicException('Expected string or instance of Node\Identifier');
    }
    /**
     * Normalizes strings to Identifier, also allowing expressions.
     *
     * @param string|Identifier|Expr $name The identifier to normalize
     *
     * @return Identifier|Expr The normalized identifier or expression
     */
    public static function normalize_identifier_or_expr($name)
    {
        if ($name instanceof Identifier || $name instanceof Expr) {
            return $name;
        }
        if (\is_string($name)) {
            return new Identifier($name);
        }
        throw new \LogicException('Expected string or instance of Node\Identifier or Node\Expr');
    }
    /**
     * Normalizes a name: Converts string names to Name nodes.
     *
     * @param Name|string $name The name to normalize
     *
     * @return Name The normalized name
     */
    public static function normalize_name($name): Name
    {
        if ($name instanceof Name) {
            return $name;
        }
        if (is_string($name)) {
            if (!$name) {
                throw new \LogicException('Name cannot be empty');
            }
            if ($name[0] === '\\') {
                return new Name\Fully_Qualified(substr($name, 1));
            }
            if (0 === strpos($name, 'namespace\\')) {
                return new Name\Relative(substr($name, strlen('namespace\\')));
            }
            return new Name($name);
        }
        throw new \LogicException('Name must be a string or an instance of Node\Name');
    }
    /**
     * Normalizes a name: Converts string names to Name nodes, while also allowing expressions.
     *
     * @param Expr|Name|string $name The name to normalize
     *
     * @return Name|Expr The normalized name or expression
     */
    public static function normalize_name_or_expr($name)
    {
        if ($name instanceof Expr) {
            return $name;
        }
        if (!is_string($name) && !$name instanceof Name) {
            throw new \LogicException('Name must be a string or an instance of Node\Name or Node\Expr');
        }
        return self::normalize_name($name);
    }
    /**
     * Normalizes a type: Converts plain-text type names into proper AST representation.
     *
     * In particular, builtin types become Identifiers, custom types become Names and nullables
     * are wrapped in NullableType nodes.
     *
     * @param string|Name|Identifier|ComplexType $type The type to normalize
     *
     * @return Name|Identifier|ComplexType The normalized type
     */
    public static function normalize_type($type)
    {
        if (!is_string($type)) {
            if (!$type instanceof Name && !$type instanceof Identifier && !$type instanceof Complex_Type) {
                throw new \LogicException('Type must be a string, or an instance of Name, Identifier or ComplexType');
            }
            return $type;
        }
        $nullable = false;
        if (strlen($type) > 0 && $type[0] === '?') {
            $nullable = true;
            $type = substr($type, 1);
        }
        $builtin_types = ['array', 'callable', 'bool', 'int', 'float', 'string', 'iterable', 'void', 'object', 'null', 'false', 'mixed', 'never', 'true'];
        $lower_type = strtolower($type);
        if (in_array($lower_type, $builtin_types)) {
            $type = new Identifier($lower_type);
        } else {
            $type = self::normalize_name($type);
        }
        $not_nullable_types = ['void', 'mixed', 'never'];
        if ($nullable && in_array((string) $type, $not_nullable_types)) {
            throw new \LogicException(sprintf('%s type cannot be nullable', $type));
        }
        return $nullable ? new Nullable_Type($type) : $type;
    }
    /**
     * Normalizes a value: Converts nulls, booleans, integers,
     * floats, strings and arrays into their respective nodes
     *
     * @param Node\Expr|bool|null|int|float|string|array|\UnitEnum $value The value to normalize
     *
     * @return Expr The normalized value
     */
    public static function normalize_value($value): Expr
    {
        if ($value instanceof Node\Expr) {
            return $value;
        }
        if (is_null($value)) {
            return new Expr\Const_Fetch(new Name('null'));
        }
        if (is_bool($value)) {
            return new Expr\Const_Fetch(new Name($value ? 'true' : 'false'));
        }
        if (is_int($value)) {
            return new Scalar\Int_($value);
        }
        if (is_float($value)) {
            return new Scalar\Float_($value);
        }
        if (is_string($value)) {
            return new Scalar\String_($value);
        }
        if (is_array($value)) {
            $items = [];
            $last_key = -1;
            foreach ($value as $item_key => $item_value) {
                // for consecutive, numeric keys don't generate keys
                if (null !== $last_key && ++$last_key === $item_key) {
                    $items[] = new Node\Array_Item(self::normalize_value($item_value));
                } else {
                    $last_key = null;
                    $items[] = new Node\Array_Item(self::normalize_value($item_value), self::normalize_value($item_key));
                }
            }
            return new Expr\Array_($items);
        }
        if ($value instanceof \Unit_Enum) {
            return new Expr\Class_Const_Fetch(new Fully_Qualified(\get_class($value)), new Identifier($value->name));
        }
        throw new \LogicException('Invalid value');
    }
    /**
     * Normalizes a doc comment: Converts plain strings to PhpParser\Comment\Doc.
     *
     * @param Comment\Doc|string $docComment The doc comment to normalize
     *
     * @return Comment\Doc The normalized doc comment
     */
    public static function normalize_doc_comment($doc_comment): Comment\Doc
    {
        if ($doc_comment instanceof Comment\Doc) {
            return $doc_comment;
        }
        if (is_string($doc_comment)) {
            return new Comment\Doc($doc_comment);
        }
        throw new \LogicException('Doc comment must be a string or an instance of PhpParser\Comment\Doc');
    }
    /**
     * Normalizes a attribute: Converts attribute to the Attribute Group if needed.
     *
     * @param Node\Attribute|Node\AttributeGroup $attribute
     *
     * @return Node\AttributeGroup The Attribute Group
     */
    public static function normalize_attribute($attribute): Node\Attribute_Group
    {
        if ($attribute instanceof Node\Attribute_Group) {
            return $attribute;
        }
        if (!$attribute instanceof Node\Attribute) {
            throw new \LogicException('Attribute must be an instance of PhpParser\Node\Attribute or PhpParser\Node\AttributeGroup');
        }
        return new Node\Attribute_Group([$attribute]);
    }
    /**
     * Adds a modifier and returns new modifier bitmask.
     *
     * @param int $modifiers Existing modifiers
     * @param int $modifier Modifier to set
     *
     * @return int New modifiers
     */
    public static function add_modifier(int $modifiers, int $modifier): int
    {
        Modifiers::verify_modifier($modifiers, $modifier);
        return $modifiers | $modifier;
    }
    /**
     * Adds a modifier and returns new modifier bitmask.
     * @return int New modifiers
     */
    public static function add_class_modifier(int $existing_modifiers, int $modifier_to_set): int
    {
        Modifiers::verify_class_modifier($existing_modifiers, $modifier_to_set);
        return $existing_modifiers | $modifier_to_set;
    }
}