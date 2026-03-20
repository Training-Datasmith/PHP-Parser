<?php

declare (strict_types=1);
namespace Php_Parser\Internal;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
/**
 * This node is used internally by the format-preserving pretty printer to print anonymous classes.
 *
 * The normal anonymous class structure violates assumptions about the order of token offsets.
 * Namely, the constructor arguments are part of the Expr\New_ node and follow the class node, even
 * though they are actually interleaved with them. This special node type is used temporarily to
 * restore a sane token offset order.
 *
 * @internal
 */
class Printable_New_Anon_Class_Node extends Expr
{
    /** @var Node\AttributeGroup[] PHP attribute groups */
    public array $attr_groups;
    /** @var int Modifiers */
    public int $flags;
    /** @var (Node\Arg|Node\VariadicPlaceholder)[] Arguments */
    public array $args;
    /** @var null|Node\Name Name of extended class */
    public ?Node\Name $extends;
    /** @var Node\Name[] Names of implemented interfaces */
    public array $implements;
    /** @var Node\Stmt[] Statements */
    public array $stmts;
    /**
     * @param Node\AttributeGroup[] $attrGroups PHP attribute groups
     * @param (Node\Arg|Node\VariadicPlaceholder)[] $args Arguments
     * @param Node\Name|null $extends Name of extended class
     * @param Node\Name[] $implements Names of implemented interfaces
     * @param Node\Stmt[] $stmts Statements
     * @param array<string, mixed> $attributes Attributes
     */
    public function __construct(array $attr_groups, int $flags, array $args, ?Node\Name $extends, array $implements, array $stmts, array $attributes)
    {
        parent::__construct($attributes);
        $this->attr_groups = $attr_groups;
        $this->flags = $flags;
        $this->args = $args;
        $this->extends = $extends;
        $this->implements = $implements;
        $this->stmts = $stmts;
    }
    public static function from_new_node(Expr\New_ $new_node): self
    {
        $class = $new_node->class;
        assert($class instanceof Node\Stmt\Class_);
        // We don't assert that $class->name is null here, to allow consumers to assign unique names
        // to anonymous classes for their own purposes. We simplify ignore the name here.
        return new self($class->attr_groups, $class->flags, $new_node->args, $class->extends, $class->implements, $class->stmts, $new_node->get_attributes());
    }
    public function get_type(): string
    {
        return 'Expr_PrintableNewAnonClass';
    }
    public function get_sub_node_names(): array
    {
        return ['attrGroups', 'flags', 'args', 'extends', 'implements', 'stmts'];
    }
}