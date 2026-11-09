<?php

declare (strict_types=1);
namespace Php_Parser\Node_Visitor;

use Php_Parser\Error_Handler;
use Php_Parser\Name_Context;
use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Name;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Parser\Node\Stmt;
use Php_Parser\Node_Visitor_Abstract;
class Name_Resolver extends Node_Visitor_Abstract
{
    /** @var NameContext Naming context */
    protected Name_Context $name_context;
    /** @var bool Whether to preserve original names */
    protected bool $preserve_original_names;
    /** @var bool Whether to replace resolved nodes in place, or to add resolvedNode attributes */
    protected bool $replace_nodes;
    /**
     * Constructs a name resolution visitor.
     *
     * Options:
     *  * preserveOriginalNames (default false): An "originalName" attribute will be added to
     *    all name nodes that underwent resolution.
     *  * replaceNodes (default true): Resolved names are replaced in-place. Otherwise, a
     *    resolvedName attribute is added. (Names that cannot be statically resolved receive a
     *    namespacedName attribute, as usual.)
     *
     * @param ErrorHandler|null $errorHandler Error handler
     * @param array{preserveOriginalNames?: bool, replaceNodes?: bool} $options Options
     */
    public function __construct(?Error_Handler $error_handler = null, array $options = [])
    {
        $this->name_context = new Name_Context($error_handler ?? new Error_Handler\Throwing());
        $this->preserve_original_names = $options['preserveOriginalNames'] ?? false;
        $this->replace_nodes = $options['replaceNodes'] ?? true;
    }
    /**
     * Get name resolution context.
     */
    public function get_name_context(): Name_Context
    {
        return $this->name_context;
    }
    public function before_traverse(array $nodes): ?array
    {
        $this->name_context->start_namespace();
        return null;
    }
    public function enter_node(Node $node)
    {
        if ($node instanceof Stmt\Namespace_) {
            $this->name_context->start_namespace($node->name);
        } elseif ($node instanceof Stmt\Use_) {
            foreach ($node->uses as $use) {
                $this->add_alias($use, $node->type);
            }
        } elseif ($node instanceof Stmt\Group_Use) {
            foreach ($node->uses as $use) {
                $this->add_alias($use, $node->type, $node->prefix);
            }
        } elseif ($node instanceof Stmt\Class_) {
            if (null !== $node->extends) {
                $node->extends = $this->resolve_class_name($node->extends);
            }
            foreach ($node->implements as &$interface) {
                $interface = $this->resolve_class_name($interface);
            }
            $this->resolve_attr_groups($node);
            if (null !== $node->name) {
                $this->add_namespaced_name($node);
            } else {
                $node->namespaced_name = null;
            }
        } elseif ($node instanceof Stmt\Interface_) {
            foreach ($node->extends as &$interface) {
                $interface = $this->resolve_class_name($interface);
            }
            $this->resolve_attr_groups($node);
            $this->add_namespaced_name($node);
        } elseif ($node instanceof Stmt\Enum_) {
            foreach ($node->implements as &$interface) {
                $interface = $this->resolve_class_name($interface);
            }
            $this->resolve_attr_groups($node);
            $this->add_namespaced_name($node);
        } elseif ($node instanceof Stmt\Trait_) {
            $this->resolve_attr_groups($node);
            $this->add_namespaced_name($node);
        } elseif ($node instanceof Stmt\Function_) {
            $this->resolve_signature($node);
            $this->resolve_attr_groups($node);
            $this->add_namespaced_name($node);
        } elseif ($node instanceof Stmt\Class_Method || $node instanceof Expr\Closure || $node instanceof Expr\Arrow_Function) {
            $this->resolve_signature($node);
            $this->resolve_attr_groups($node);
        } elseif ($node instanceof Stmt\Property) {
            if (null !== $node->type) {
                $node->type = $this->resolve_type($node->type);
            }
            $this->resolve_attr_groups($node);
        } elseif ($node instanceof Node\Property_Hook) {
            foreach ($node->params as $param) {
                $param->type = $this->resolve_type($param->type);
                $this->resolve_attr_groups($param);
            }
            $this->resolve_attr_groups($node);
        } elseif ($node instanceof Stmt\Const_) {
            foreach ($node->consts as $const) {
                $this->add_namespaced_name($const);
            }
            $this->resolve_attr_groups($node);
        } elseif ($node instanceof Stmt\Class_Const) {
            if (null !== $node->type) {
                $node->type = $this->resolve_type($node->type);
            }
            $this->resolve_attr_groups($node);
        } elseif ($node instanceof Stmt\Enum_Case) {
            $this->resolve_attr_groups($node);
        } elseif ($node instanceof Expr\Static_Call || $node instanceof Expr\Static_Property_Fetch || $node instanceof Expr\Class_Const_Fetch || $node instanceof Expr\New_ || $node instanceof Expr\Instanceof_) {
            if ($node->class instanceof Name) {
                $node->class = $this->resolve_class_name($node->class);
            }
        } elseif ($node instanceof Stmt\Catch_) {
            foreach ($node->types as &$type) {
                $type = $this->resolve_class_name($type);
            }
        } elseif ($node instanceof Expr\Func_Call) {
            if ($node->name instanceof Name) {
                $node->name = $this->resolve_name($node->name, Stmt\Use_::TYPE_FUNCTION);
            }
        } elseif ($node instanceof Expr\Const_Fetch) {
            $node->name = $this->resolve_name($node->name, Stmt\Use_::TYPE_CONSTANT);
        } elseif ($node instanceof Stmt\Trait_Use) {
            foreach ($node->traits as &$trait) {
                $trait = $this->resolve_class_name($trait);
            }
            foreach ($node->adaptations as $adaptation) {
                if (null !== $adaptation->trait) {
                    $adaptation->trait = $this->resolve_class_name($adaptation->trait);
                }
                if ($adaptation instanceof Stmt\Trait_Use_Adaptation\Precedence) {
                    foreach ($adaptation->insteadof as &$insteadof) {
                        $insteadof = $this->resolve_class_name($insteadof);
                    }
                }
            }
        }
        return null;
    }
    /** @param Stmt\Use_::TYPE_* $type */
    private function add_alias(Node\Use_Item $use, int $type, ?Name $prefix = null): void
    {
        // Add prefix for group uses
        $name = $prefix ? Name::concat($prefix, $use->name) : $use->name;
        // Type is determined either by individual element or whole use declaration
        $type |= $use->type;
        $this->name_context->add_alias($name, (string) $use->get_alias(), $type, $use->get_attributes());
    }
    /** @param Stmt\Function_|Stmt\ClassMethod|Expr\Closure|Expr\ArrowFunction $node */
    private function resolve_signature($node): void
    {
        foreach ($node->params as $param) {
            $param->type = $this->resolve_type($param->type);
            $this->resolve_attr_groups($param);
        }
        $node->return_type = $this->resolve_type($node->return_type);
    }
    /**
     * @template T of Node\Identifier|Name|Node\ComplexType|null
     * @param T $node
     * @return T
     */
    private function resolve_type(?Node $node): ?Node
    {
        if ($node instanceof Name) {
            return $this->resolve_class_name($node);
        }
        if ($node instanceof Node\Nullable_Type) {
            $node->type = $this->resolve_type($node->type);
            return $node;
        }
        if ($node instanceof Node\Union_Type || $node instanceof Node\Intersection_Type) {
            foreach ($node->types as &$type) {
                $type = $this->resolve_type($type);
            }
            return $node;
        }
        return $node;
    }
    /**
     * Resolve name, according to name resolver options.
     *
     * @param Name $name Function or constant name to resolve
     * @param Stmt\Use_::TYPE_* $type One of Stmt\Use_::TYPE_*
     *
     * @return Name Resolved name, or original name with attribute
     */
    protected function resolve_name(Name $name, int $type): Name
    {
        if (!$this->replace_nodes) {
            $resolved_name = $this->name_context->get_resolved_name($name, $type);
            if (null !== $resolved_name) {
                $name->set_attribute('resolvedName', $resolved_name);
            } else {
                $name->set_attribute('namespacedName', Fully_Qualified::concat($this->name_context->get_namespace(), $name, $name->get_attributes()));
            }
            return $name;
        }
        if ($this->preserve_original_names) {
            // Save the original name
            $original_name = $name;
            $name = clone $original_name;
            $name->set_attribute('originalName', $original_name);
        }
        $resolved_name = $this->name_context->get_resolved_name($name, $type);
        if (null !== $resolved_name) {
            return $resolved_name;
        }
        // unqualified names inside a namespace cannot be resolved at compile-time
        // add the namespaced version of the name as an attribute
        $name->set_attribute('namespacedName', Fully_Qualified::concat($this->name_context->get_namespace(), $name, $name->get_attributes()));
        return $name;
    }
    protected function resolve_class_name(Name $name): Name
    {
        return $this->resolve_name($name, Stmt\Use_::TYPE_NORMAL);
    }
    protected function add_namespaced_name(Node $node): void
    {
        $node->namespaced_name = Name::concat($this->name_context->get_namespace(), (string) $node->name);
    }
    protected function resolve_attr_groups(Node $node): void
    {
        foreach ($node->attr_groups as $attr_group) {
            foreach ($attr_group->attrs as $attr) {
                $attr->name = $this->resolve_class_name($attr->name);
            }
        }
    }
}