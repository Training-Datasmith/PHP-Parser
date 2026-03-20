<?php

declare (strict_types=1);
namespace Php_Parser;

use Php_Parser\Internal\Diff_Elem;
use Php_Parser\Internal\Differ;
use Php_Parser\Internal\Printable_New_Anon_Class_Node;
use Php_Parser\Internal\Token_Stream;
use Php_Parser\Node\Attribute_Group;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Assign_Op;
use Php_Parser\Node\Expr\Binary_Op;
use Php_Parser\Node\Expr\Cast;
use Php_Parser\Node\Intersection_Type;
use Php_Parser\Node\Match_Arm;
use Php_Parser\Node\Param;
use Php_Parser\Node\Property_Hook;
use Php_Parser\Node\Scalar;
use Php_Parser\Node\Stmt;
use Php_Parser\Node\Union_Type;
abstract class Pretty_Printer_Abstract implements Pretty_Printer
{
    protected const FIXUP_PREC_LEFT = 0;
    // LHS operand affected by precedence
    protected const FIXUP_PREC_RIGHT = 1;
    // RHS operand affected by precedence
    protected const FIXUP_PREC_UNARY = 2;
    // Only operand affected by precedence
    protected const FIXUP_CALL_LHS = 3;
    // LHS of call
    protected const FIXUP_DEREF_LHS = 4;
    // LHS of dereferencing operation
    protected const FIXUP_STATIC_DEREF_LHS = 5;
    // LHS of static dereferencing operation
    protected const FIXUP_BRACED_NAME = 6;
    // Name operand that may require bracing
    protected const FIXUP_VAR_BRACED_NAME = 7;
    // Name operand that may require ${} bracing
    protected const FIXUP_ENCAPSED = 8;
    // Encapsed string part
    protected const FIXUP_NEW = 9;
    // New/instanceof operand
    protected const MAX_PRECEDENCE = 1000;
    /** @var array<class-string, array{int, int, int}> */
    protected array $precedence_map = [
        // [precedence, precedenceLHS, precedenceRHS]
        // Where the latter two are the precedences to use for the LHS and RHS of a binary operator,
        // where 1 is added to one of the sides depending on associativity. This information is not
        // used for unary operators and set to -1.
        Expr\Clone_::class => [-10, 0, 1],
        Binary_Op\Pow::class => [0, 0, 1],
        Expr\Bitwise_Not::class => [10, -1, -1],
        Expr\Unary_Plus::class => [10, -1, -1],
        Expr\Unary_Minus::class => [10, -1, -1],
        Cast\Int_::class => [10, -1, -1],
        Cast\Double::class => [10, -1, -1],
        Cast\String_::class => [10, -1, -1],
        Cast\Array_::class => [10, -1, -1],
        Cast\Object_::class => [10, -1, -1],
        Cast\Bool_::class => [10, -1, -1],
        Cast\Unset_::class => [10, -1, -1],
        Expr\Error_Suppress::class => [10, -1, -1],
        Expr\Instanceof_::class => [20, -1, -1],
        Expr\Boolean_Not::class => [30, -1, -1],
        Binary_Op\Mul::class => [40, 41, 40],
        Binary_Op\Div::class => [40, 41, 40],
        Binary_Op\Mod::class => [40, 41, 40],
        Binary_Op\Plus::class => [50, 51, 50],
        Binary_Op\Minus::class => [50, 51, 50],
        // FIXME: This precedence is incorrect for PHP 8.
        Binary_Op\Concat::class => [50, 51, 50],
        Binary_Op\Shift_Left::class => [60, 61, 60],
        Binary_Op\Shift_Right::class => [60, 61, 60],
        Binary_Op\Pipe::class => [65, 66, 65],
        Binary_Op\Smaller::class => [70, 70, 70],
        Binary_Op\Smaller_Or_Equal::class => [70, 70, 70],
        Binary_Op\Greater::class => [70, 70, 70],
        Binary_Op\Greater_Or_Equal::class => [70, 70, 70],
        Binary_Op\Equal::class => [80, 80, 80],
        Binary_Op\Not_Equal::class => [80, 80, 80],
        Binary_Op\Identical::class => [80, 80, 80],
        Binary_Op\Not_Identical::class => [80, 80, 80],
        Binary_Op\Spaceship::class => [80, 80, 80],
        Binary_Op\Bitwise_And::class => [90, 91, 90],
        Binary_Op\Bitwise_Xor::class => [100, 101, 100],
        Binary_Op\Bitwise_Or::class => [110, 111, 110],
        Binary_Op\Boolean_And::class => [120, 121, 120],
        Binary_Op\Boolean_Or::class => [130, 131, 130],
        Binary_Op\Coalesce::class => [140, 140, 141],
        Expr\Ternary::class => [150, 150, 150],
        Expr\Assign::class => [160, -1, -1],
        Expr\Assign_Ref::class => [160, -1, -1],
        Assign_Op\Plus::class => [160, -1, -1],
        Assign_Op\Minus::class => [160, -1, -1],
        Assign_Op\Mul::class => [160, -1, -1],
        Assign_Op\Div::class => [160, -1, -1],
        Assign_Op\Concat::class => [160, -1, -1],
        Assign_Op\Mod::class => [160, -1, -1],
        Assign_Op\Bitwise_And::class => [160, -1, -1],
        Assign_Op\Bitwise_Or::class => [160, -1, -1],
        Assign_Op\Bitwise_Xor::class => [160, -1, -1],
        Assign_Op\Shift_Left::class => [160, -1, -1],
        Assign_Op\Shift_Right::class => [160, -1, -1],
        Assign_Op\Pow::class => [160, -1, -1],
        Assign_Op\Coalesce::class => [160, -1, -1],
        Expr\Yield_From::class => [170, -1, -1],
        Expr\Yield_::class => [175, -1, -1],
        Expr\Print_::class => [180, -1, -1],
        Binary_Op\Logical_And::class => [190, 191, 190],
        Binary_Op\Logical_Xor::class => [200, 201, 200],
        Binary_Op\Logical_Or::class => [210, 211, 210],
        Expr\Include_::class => [220, -1, -1],
        Expr\Arrow_Function::class => [230, -1, -1],
        Expr\Throw_::class => [240, -1, -1],
        Expr\Cast\Void_::class => [250, -1, -1],
    ];
    /** @var int Current indentation level. */
    protected int $indent_level;
    /** @var string String for single level of indentation */
    private string $indent;
    /** @var int Width in spaces to indent by. */
    private int $indent_width;
    /** @var bool Whether to use tab indentation. */
    private bool $use_tabs;
    /** @var int Width in spaces of one tab. */
    private int $tab_width = 4;
    /** @var string Newline style. Does not include current indentation. */
    protected string $newline;
    /** @var string Newline including current indentation. */
    protected string $nl;
    /** @var string|null Token placed at end of doc string to ensure it is followed by a newline.
     *                   Null if flexible doc strings are used. */
    protected ?string $doc_string_end_token;
    /** @var bool Whether semicolon namespaces can be used (i.e. no global namespace is used) */
    protected bool $can_use_semicolon_namespaces;
    /** @var bool Whether to use short array syntax if the node specifies no preference */
    protected bool $short_array_syntax;
    /** @var PhpVersion PHP version to target */
    protected Php_Version $php_version;
    /** @var TokenStream|null Original tokens for use in format-preserving pretty print */
    protected ?Token_Stream $orig_tokens = null;
    /** @var Internal\Differ<Node> Differ for node lists */
    protected Differ $node_list_differ;
    /** @var array<string, bool> Map determining whether a certain character is a label character */
    protected array $label_char_map;
    /**
     * @var array<string, array<string, int>> Map from token classes and subnode names to FIXUP_* constants.
     *                                        This is used during format-preserving prints to place additional parens/braces if necessary.
     */
    protected array $fixup_map;
    /**
     * @var array<string, array{left?: int|string, right?: int|string}> Map from "{$node->getType()}->{$subNode}"
     *                                                                  to ['left' => $l, 'right' => $r], where $l and $r specify the token type that needs to be stripped
     *                                                                  when removing this node.
     */
    protected array $removal_map;
    /**
     * @var array<string, array{int|string|null, bool, string|null, string|null}> Map from
     *                                                                            "{$node->getType()}->{$subNode}" to [$find, $beforeToken, $extraLeft, $extraRight].
     *                                                                            $find is an optional token after which the insertion occurs. $extraLeft/Right
     *                                                                            are optionally added before/after the main insertions.
     */
    protected array $insertion_map;
    /**
     * @var array<string, string> Map From "{$class}->{$subNode}" to string that should be inserted
     *                            between elements of this list subnode.
     */
    protected array $list_insertion_map;
    /**
     * @var array<string, array{int|string|null, string, string}>
     */
    protected array $empty_list_insertion_map;
    /** @var array<string, array{string, int, int}>
     *       Map from "{$class}->{$subNode}" to [$printFn, $skipToken, $findToken] where $printFn is the function to
     *       print the modifiers, $skipToken is the token to skip at the start and $findToken is the token before which
     *       the modifiers should be reprinted. */
    protected array $modifier_change_map;
    /**
     * Creates a pretty printer instance using the given options.
     *
     * Supported options:
     *  * PhpVersion $phpVersion: The PHP version to target (default to PHP 7.4). This option
     *                            controls compatibility of the generated code with older PHP
     *                            versions in cases where a simple stylistic choice exists (e.g.
     *                            array() vs []). It is safe to pretty-print an AST for a newer
     *                            PHP version while specifying an older target (but the result will
     *                            of course not be compatible with the older version in that case).
     *  * string $newline:        The newline style to use. Should be "\n" (default) or "\r\n".
     *  * string $indent:         The indentation to use. Should either be all spaces or a single
     *                            tab. Defaults to four spaces ("    ").
     *  * bool $shortArraySyntax: Whether to use [] instead of array() as the default array
     *                            syntax, if the node does not specify a format. Defaults to whether
     *                            the phpVersion support short array syntax.
     *
     * @param array{
     *     phpVersion?: PhpVersion, newline?: string, indent?: string, shortArraySyntax?: bool
     * } $options Dictionary of formatting options
     */
    public function __construct(array $options = [])
    {
        $this->php_version = $options['phpVersion'] ?? Php_Version::from_components(7, 4);
        $this->newline = $options['newline'] ?? "\n";
        if ($this->newline !== "\n" && $this->newline != "\r\n") {
            throw new \LogicException('Option "newline" must be one of "\n" or "\r\n"');
        }
        $this->short_array_syntax = $options['shortArraySyntax'] ?? $this->php_version->supports_short_array_syntax();
        $this->doc_string_end_token = $this->php_version->supports_flexible_heredoc() ? null : '_DOC_STRING_END_' . mt_rand();
        $this->indent = $indent = $options['indent'] ?? '    ';
        if ($indent === "\t") {
            $this->use_tabs = true;
            $this->indent_width = $this->tab_width;
        } elseif ($indent === \str_repeat(' ', \strlen($indent))) {
            $this->use_tabs = false;
            $this->indent_width = \strlen($indent);
        } else {
            throw new \LogicException('Option "indent" must either be all spaces or a single tab');
        }
    }
    /**
     * Reset pretty printing state.
     */
    protected function reset_state(): void
    {
        $this->indent_level = 0;
        $this->nl = $this->newline;
        $this->orig_tokens = null;
    }
    /**
     * Set indentation level
     *
     * @param int $level Level in number of spaces
     */
    protected function set_indent_level(int $level): void
    {
        $this->indent_level = $level;
        if ($this->use_tabs) {
            $tabs = \intdiv($level, $this->tab_width);
            $spaces = $level % $this->tab_width;
            $this->nl = $this->newline . \str_repeat("\t", $tabs) . \str_repeat(' ', $spaces);
        } else {
            $this->nl = $this->newline . \str_repeat(' ', $level);
        }
    }
    /**
     * Increase indentation level.
     */
    protected function indent(): void
    {
        $this->indent_level += $this->indent_width;
        $this->nl .= $this->indent;
    }
    /**
     * Decrease indentation level.
     */
    protected function outdent(): void
    {
        assert($this->indent_level >= $this->indent_width);
        $this->set_indent_level($this->indent_level - $this->indent_width);
    }
    /**
     * Pretty prints an array of statements.
     *
     * @param Node[] $stmts Array of statements
     *
     * @return string Pretty printed statements
     */
    public function pretty_print(array $stmts): string
    {
        $this->reset_state();
        $this->preprocess_nodes($stmts);
        return ltrim($this->handle_magic_tokens($this->p_stmts($stmts, false)));
    }
    /**
     * Pretty prints an expression.
     *
     * @param Expr $node Expression node
     *
     * @return string Pretty printed node
     */
    public function pretty_print_expr(Expr $node): string
    {
        $this->reset_state();
        return $this->handle_magic_tokens($this->p($node));
    }
    /**
     * Pretty prints a file of statements (includes the opening <?php tag if it is required).
     *
     * @param Node[] $stmts Array of statements
     *
     * @return string Pretty printed statements
     */
    public function pretty_print_file(array $stmts): string
    {
        if (!$stmts) {
            return '<?php' . $this->newline . $this->newline;
        }
        $p = '<?php' . $this->newline . $this->newline . $this->pretty_print($stmts);
        if ($stmts[0] instanceof Stmt\Inline_Html) {
            $p = preg_replace('/^<\?php\s+\?>\r?\n?/', '', $p);
        }
        if ($stmts[count($stmts) - 1] instanceof Stmt\Inline_Html) {
            return preg_replace('/<\?php$/', '', rtrim($p));
        }
        return $p;
    }
    /**
     * Preprocesses the top-level nodes to initialize pretty printer state.
     *
     * @param Node[] $nodes Array of nodes
     */
    protected function preprocess_nodes(array $nodes): void
    {
        /* We can use semicolon-namespaces unless there is a global namespace declaration */
        $this->can_use_semicolon_namespaces = true;
        foreach ($nodes as $node) {
            if ($node instanceof Stmt\Namespace_ && null === $node->name) {
                $this->can_use_semicolon_namespaces = false;
                break;
            }
        }
    }
    /**
     * Handles (and removes) doc-string-end tokens.
     */
    protected function handle_magic_tokens(string $str): string
    {
        if ($this->doc_string_end_token !== null) {
            // Replace doc-string-end tokens with nothing or a newline
            $str = str_replace($this->doc_string_end_token . ';' . $this->newline, ';' . $this->newline, $str);
            $str = str_replace($this->doc_string_end_token, $this->newline, $str);
        }
        return $str;
    }
    /**
     * Pretty prints an array of nodes (statements) and indents them optionally.
     *
     * @param Node[] $nodes Array of nodes
     * @param bool $indent Whether to indent the printed nodes
     *
     * @return string Pretty printed statements
     */
    protected function p_stmts(array $nodes, bool $indent = true): string
    {
        if ($indent) {
            $this->indent();
        }
        $result = '';
        foreach ($nodes as $node) {
            $comments = $node->get_comments();
            if ($comments) {
                $result .= $this->nl . $this->p_comments($comments);
                if ($node instanceof Stmt\Nop) {
                    continue;
                }
            }
            $result .= $this->nl . $this->p($node);
        }
        if ($indent) {
            $this->outdent();
        }
        return $result;
    }
    /**
     * Pretty-print an infix operation while taking precedence into account.
     *
     * @param string $class Node class of operator
     * @param Node $leftNode Left-hand side node
     * @param string $operatorString String representation of the operator
     * @param Node $rightNode Right-hand side node
     * @param int $precedence Precedence of parent operator
     * @param int $lhsPrecedence Precedence for unary operator on LHS of binary operator
     *
     * @return string Pretty printed infix operation
     */
    protected function p_infix_op(string $class, Node $left_node, string $operator_string, Node $right_node, int $precedence, int $lhs_precedence): string
    {
        [$op_precedence, $new_precedence_lhs, $new_precedence_rhs] = $this->precedence_map[$class];
        $prefix = '';
        $suffix = '';
        if ($op_precedence >= $precedence) {
            $prefix = '(';
            $suffix = ')';
            $lhs_precedence = self::MAX_PRECEDENCE;
        }
        return $prefix . $this->p($left_node, $new_precedence_lhs, $new_precedence_lhs) . $operator_string . $this->p($right_node, $new_precedence_rhs, $lhs_precedence) . $suffix;
    }
    /**
     * Pretty-print a prefix operation while taking precedence into account.
     *
     * @param string $class Node class of operator
     * @param string $operatorString String representation of the operator
     * @param Node $node Node
     * @param int $precedence Precedence of parent operator
     * @param int $lhsPrecedence Precedence for unary operator on LHS of binary operator
     *
     * @return string Pretty printed prefix operation
     */
    protected function p_prefix_op(string $class, string $operator_string, Node $node, int $precedence, int $lhs_precedence): string
    {
        $op_precedence = $this->precedence_map[$class][0];
        $prefix = '';
        $suffix = '';
        if ($op_precedence >= $lhs_precedence) {
            $prefix = '(';
            $suffix = ')';
            $lhs_precedence = self::MAX_PRECEDENCE;
        }
        $printed_arg = $this->p($node, $op_precedence, $lhs_precedence);
        if ($operator_string === '+' && $printed_arg[0] === '+' || $operator_string === '-' && $printed_arg[0] === '-') {
            // Avoid printing +(+$a) as ++$a and similar.
            $printed_arg = '(' . $printed_arg . ')';
        }
        return $prefix . $operator_string . $printed_arg . $suffix;
    }
    /**
     * Pretty-print a postfix operation while taking precedence into account.
     *
     * @param string $class Node class of operator
     * @param string $operatorString String representation of the operator
     * @param Node $node Node
     * @param int $precedence Precedence of parent operator
     * @param int $lhsPrecedence Precedence for unary operator on LHS of binary operator
     *
     * @return string Pretty printed postfix operation
     */
    protected function p_postfix_op(string $class, Node $node, string $operator_string, int $precedence, int $lhs_precedence): string
    {
        $op_precedence = $this->precedence_map[$class][0];
        $prefix = '';
        $suffix = '';
        if ($op_precedence >= $precedence) {
            $prefix = '(';
            $suffix = ')';
            $lhs_precedence = self::MAX_PRECEDENCE;
        }
        if ($op_precedence < $lhs_precedence) {
            $lhs_precedence = $op_precedence;
        }
        return $prefix . $this->p($node, $op_precedence, $lhs_precedence) . $operator_string . $suffix;
    }
    /**
     * Pretty prints an array of nodes and implodes the printed values.
     *
     * @param Node[] $nodes Array of Nodes to be printed
     * @param string $glue Character to implode with
     *
     * @return string Imploded pretty printed nodes> $pre
     */
    protected function p_implode(array $nodes, string $glue = ''): string
    {
        $p_nodes = [];
        foreach ($nodes as $node) {
            if (null === $node) {
                $p_nodes[] = '';
            } else {
                $p_nodes[] = $this->p($node);
            }
        }
        return implode($glue, $p_nodes);
    }
    /**
     * Pretty prints an array of nodes and implodes the printed values with commas.
     *
     * @param Node[] $nodes Array of Nodes to be printed
     *
     * @return string Comma separated pretty printed nodes
     */
    protected function p_comma_separated(array $nodes): string
    {
        return $this->p_implode($nodes, ', ');
    }
    /**
     * Pretty prints a comma-separated list of nodes in multiline style, including comments.
     *
     * The result includes a leading newline and one level of indentation (same as pStmts).
     *
     * @param Node[] $nodes Array of Nodes to be printed
     * @param bool $trailingComma Whether to use a trailing comma
     *
     * @return string Comma separated pretty printed nodes in multiline style
     */
    protected function p_comma_separated_multiline(array $nodes, bool $trailing_comma): string
    {
        $this->indent();
        $result = '';
        $last_idx = count($nodes) - 1;
        foreach ($nodes as $idx => $node) {
            if ($node !== null) {
                $comments = $node->get_comments();
                if ($comments) {
                    $result .= $this->nl . $this->p_comments($comments);
                }
                $result .= $this->nl . $this->p($node);
            } else {
                $result .= $this->nl;
            }
            if ($trailing_comma || $idx !== $last_idx) {
                $result .= ',';
            }
        }
        $this->outdent();
        return $result;
    }
    /**
     * Prints reformatted text of the passed comments.
     *
     * @param Comment[] $comments List of comments
     *
     * @return string Reformatted text of comments
     */
    protected function p_comments(array $comments): string
    {
        $formatted_comments = [];
        foreach ($comments as $comment) {
            $formatted_comments[] = str_replace("\n", $this->nl, $comment->get_reformatted_text());
        }
        return implode($this->nl, $formatted_comments);
    }
    /**
     * Perform a format-preserving pretty print of an AST.
     *
     * The format preservation is best effort. For some changes to the AST the formatting will not
     * be preserved (at least not locally).
     *
     * In order to use this method a number of prerequisites must be satisfied:
     *  * The startTokenPos and endTokenPos attributes in the lexer must be enabled.
     *  * The CloningVisitor must be run on the AST prior to modification.
     *  * The original tokens must be provided, using the getTokens() method on the lexer.
     *
     * @param Node[] $stmts Modified AST with links to original AST
     * @param Node[] $origStmts Original AST with token offset information
     * @param Token[] $origTokens Tokens of the original code
     */
    public function print_format_preserving(array $stmts, array $orig_stmts, array $orig_tokens): string
    {
        $this->initialize_node_list_differ();
        $this->initialize_label_char_map();
        $this->initialize_fixup_map();
        $this->initialize_removal_map();
        $this->initialize_insertion_map();
        $this->initialize_list_insertion_map();
        $this->initialize_empty_list_insertion_map();
        $this->initialize_modifier_change_map();
        $this->reset_state();
        $this->orig_tokens = new Token_Stream($orig_tokens, $this->tab_width);
        $this->preprocess_nodes($stmts);
        $pos = 0;
        $result = $this->p_array($stmts, $orig_stmts, $pos, 0, 'File', 'stmts', null);
        if (null !== $result) {
            $result .= $this->orig_tokens->get_token_code($pos, count($orig_tokens) - 1, 0);
        } else {
            // Fallback
            // TODO Add <?php properly
            $result = '<?php' . $this->newline . $this->p_stmts($stmts, false);
        }
        return $this->handle_magic_tokens($result);
    }
    protected function p_fallback(Node $node, int $precedence, int $lhs_precedence): string
    {
        return $this->{'p' . $node->get_type()}($node, $precedence, $lhs_precedence);
    }
    /**
     * Pretty prints a node.
     *
     * This method also handles formatting preservation for nodes.
     *
     * @param Node $node Node to be pretty printed
     * @param int $precedence Precedence of parent operator
     * @param int $lhsPrecedence Precedence for unary operator on LHS of binary operator
     * @param bool $parentFormatPreserved Whether parent node has preserved formatting
     *
     * @return string Pretty printed node
     */
    protected function p(Node $node, int $precedence = self::MAX_PRECEDENCE, int $lhs_precedence = self::MAX_PRECEDENCE, bool $parent_format_preserved = false): string
    {
        // No orig tokens means this is a normal pretty print without preservation of formatting
        if (!$this->orig_tokens) {
            return $this->{'p' . $node->get_type()}($node, $precedence, $lhs_precedence);
        }
        /** @var Node|null $origNode */
        $orig_node = $node->get_attribute('origNode');
        if (null === $orig_node) {
            return $this->p_fallback($node, $precedence, $lhs_precedence);
        }
        $class = \get_class($node);
        \assert($class === \get_class($orig_node));
        $start_pos = $orig_node->get_start_token_pos();
        $end_pos = $orig_node->get_end_token_pos();
        \assert($start_pos >= 0 && $end_pos >= 0);
        $fallback_node = $node;
        if ($node instanceof Expr\New_ && $node->class instanceof Stmt\Class_) {
            // Normalize node structure of anonymous classes
            assert($orig_node instanceof Expr\New_);
            $node = Printable_New_Anon_Class_Node::from_new_node($node);
            $orig_node = Printable_New_Anon_Class_Node::from_new_node($orig_node);
            $class = Printable_New_Anon_Class_Node::class;
        }
        // InlineHTML node does not contain closing and opening PHP tags. If the parent formatting
        // is not preserved, then we need to use the fallback code to make sure the tags are
        // printed.
        if ($node instanceof Stmt\Inline_Html && !$parent_format_preserved) {
            return $this->p_fallback($fallback_node, $precedence, $lhs_precedence);
        }
        $indent_adjustment = $this->indent_level - $this->orig_tokens->get_indentation_before($start_pos);
        $type = $node->get_type();
        $fixup_info = $this->fixup_map[$class] ?? null;
        $result = '';
        $pos = $start_pos;
        foreach ($node->get_sub_node_names() as $sub_node_name) {
            $sub_node = $node->{$sub_node_name};
            $orig_sub_node = $orig_node->{$sub_node_name};
            if (!$sub_node instanceof Node && $sub_node !== null || !$orig_sub_node instanceof Node && $orig_sub_node !== null) {
                if ($sub_node === $orig_sub_node) {
                    // Unchanged, can reuse old code
                    continue;
                }
                if (is_array($sub_node) && is_array($orig_sub_node)) {
                    // Array subnode changed, we might be able to reconstruct it
                    $list_result = $this->p_array($sub_node, $orig_sub_node, $pos, $indent_adjustment, $class, $sub_node_name, $fixup_info[$sub_node_name] ?? null);
                    if (null === $list_result) {
                        return $this->p_fallback($fallback_node, $precedence, $lhs_precedence);
                    }
                    $result .= $list_result;
                    continue;
                }
                // Check if this is a modifier change
                $key = $class . '->' . $sub_node_name;
                if (!isset($this->modifier_change_map[$key])) {
                    return $this->p_fallback($fallback_node, $precedence, $lhs_precedence);
                }
                [$print_fn, $skip_token, $find_token] = $this->modifier_change_map[$key];
                $skip_ws_pos = $this->orig_tokens->skip_right($pos, $skip_token);
                $result .= $this->orig_tokens->get_token_code($pos, $skip_ws_pos, $indent_adjustment);
                $result .= $this->{$print_fn}($sub_node);
                $pos = $this->orig_tokens->find_right($skip_ws_pos, $find_token);
                continue;
            }
            $extra_left = '';
            $extra_right = '';
            if ($orig_sub_node !== null) {
                $sub_start_pos = $orig_sub_node->get_start_token_pos();
                $sub_end_pos = $orig_sub_node->get_end_token_pos();
                \assert($sub_start_pos >= 0 && $sub_end_pos >= 0);
            } else {
                if ($sub_node === null) {
                    // Both null, nothing to do
                    continue;
                }
                // A node has been inserted, check if we have insertion information for it
                $key = $type . '->' . $sub_node_name;
                if (!isset($this->insertion_map[$key])) {
                    return $this->p_fallback($fallback_node, $precedence, $lhs_precedence);
                }
                [$find_token, $before_token, $extra_left, $extra_right] = $this->insertion_map[$key];
                if (null !== $find_token) {
                    $sub_start_pos = $this->orig_tokens->find_right($pos, $find_token) + (int) !$before_token;
                } else {
                    $sub_start_pos = $pos;
                }
                if (null === $extra_left && null !== $extra_right) {
                    // If inserting on the right only, skipping whitespace looks better
                    $sub_start_pos = $this->orig_tokens->skip_right_whitespace($sub_start_pos);
                }
                $sub_end_pos = $sub_start_pos - 1;
            }
            if (null === $sub_node) {
                // A node has been removed, check if we have removal information for it
                $key = $type . '->' . $sub_node_name;
                if (!isset($this->removal_map[$key])) {
                    return $this->p_fallback($fallback_node, $precedence, $lhs_precedence);
                }
                // Adjust positions to account for additional tokens that must be skipped
                $removal_info = $this->removal_map[$key];
                if (isset($removal_info['left'])) {
                    $sub_start_pos = $this->orig_tokens->skip_left($sub_start_pos - 1, $removal_info['left']) + 1;
                }
                if (isset($removal_info['right'])) {
                    $sub_end_pos = $this->orig_tokens->skip_right($sub_end_pos + 1, $removal_info['right']) - 1;
                }
            }
            $result .= $this->orig_tokens->get_token_code($pos, $sub_start_pos, $indent_adjustment);
            if (null !== $sub_node) {
                $result .= $extra_left;
                $orig_indent_level = $this->indent_level;
                $this->set_indent_level(max($this->orig_tokens->get_indentation_before($sub_start_pos) + $indent_adjustment, 0));
                // If it's the same node that was previously in this position, it certainly doesn't
                // need fixup. It's important to check this here, because our fixup checks are more
                // conservative than strictly necessary.
                if (isset($fixup_info[$sub_node_name]) && $sub_node->get_attribute('origNode') !== $orig_sub_node) {
                    $fixup = $fixup_info[$sub_node_name];
                    $res = $this->p_fixup($fixup, $sub_node, $class, $sub_start_pos, $sub_end_pos);
                } else {
                    $res = $this->p($sub_node, self::MAX_PRECEDENCE, self::MAX_PRECEDENCE, true);
                }
                $this->safe_append($result, $res);
                $this->set_indent_level($orig_indent_level);
                $result .= $extra_right;
            }
            $pos = $sub_end_pos + 1;
        }
        return $result . $this->orig_tokens->get_token_code($pos, $end_pos + 1, $indent_adjustment);
    }
    /**
     * Perform a format-preserving pretty print of an array.
     *
     * @param Node[] $nodes New nodes
     * @param Node[] $origNodes Original nodes
     * @param int $pos Current token position (updated by reference)
     * @param int $indentAdjustment Adjustment for indentation
     * @param string $parentNodeClass Class of the containing node.
     * @param string $subNodeName Name of array subnode.
     * @param null|int $fixup Fixup information for array item nodes
     *
     * @return null|string Result of pretty print or null if cannot preserve formatting
     */
    protected function p_array(array $nodes, array $orig_nodes, int &$pos, int $indent_adjustment, string $parent_node_class, string $sub_node_name, ?int $fixup): ?string
    {
        $diff = $this->node_list_differ->diff_with_replacements($orig_nodes, $nodes);
        $map_key = $parent_node_class . '->' . $sub_node_name;
        $insert_str = $this->list_insertion_map[$map_key] ?? null;
        $is_stmt_list = $sub_node_name === 'stmts';
        $before_first_keep_or_replace = true;
        $skip_removed_node = false;
        $delayed_add = [];
        $last_elem_indent_level = $this->indent_level;
        $insert_newline = false;
        if ($insert_str === "\n") {
            $insert_str = '';
            $insert_newline = true;
        }
        if ($is_stmt_list && \count($orig_nodes) === 1 && \count($nodes) !== 1) {
            $start_pos = $orig_nodes[0]->get_start_token_pos();
            $end_pos = $orig_nodes[0]->get_end_token_pos();
            \assert($start_pos >= 0 && $end_pos >= 0);
            if (!$this->orig_tokens->have_braces($start_pos, $end_pos)) {
                // This was a single statement without braces, but either additional statements
                // have been added, or the single statement has been removed. This requires the
                // addition of braces. For now fall back.
                // TODO: Try to preserve formatting
                return null;
            }
        }
        $result = '';
        foreach ($diff as $i => $diff_elem) {
            $diff_type = $diff_elem->type;
            /** @var Node|string|null $arrItem */
            $arr_item = $diff_elem->new;
            /** @var Node|string|null $origArrItem */
            $orig_arr_item = $diff_elem->old;
            if ($diff_type === Diff_Elem::TYPE_KEEP || $diff_type === Diff_Elem::TYPE_REPLACE) {
                $before_first_keep_or_replace = false;
                if ($orig_arr_item === null || $arr_item === null) {
                    // We can only handle the case where both are null
                    if ($orig_arr_item === $arr_item) {
                        continue;
                    }
                    return null;
                }
                if (!$arr_item instanceof Node || !$orig_arr_item instanceof Node) {
                    // We can only deal with nodes. This can occur for Names, which use string arrays.
                    return null;
                }
                $item_start_pos = $orig_arr_item->get_start_token_pos();
                $item_end_pos = $orig_arr_item->get_end_token_pos();
                \assert($item_start_pos >= 0 && $item_end_pos >= 0 && $item_start_pos >= $pos);
                $orig_indent_level = $this->indent_level;
                $last_elem_indent_level = max($this->orig_tokens->get_indentation_before($item_start_pos) + $indent_adjustment, 0);
                $this->set_indent_level($last_elem_indent_level);
                $comments = $arr_item->get_comments();
                $orig_comments = $orig_arr_item->get_comments();
                $comment_start_pos = $orig_comments ? $orig_comments[0]->get_start_token_pos() : $item_start_pos;
                \assert($comment_start_pos >= 0);
                if ($comment_start_pos < $pos) {
                    // Comments may be assigned to multiple nodes if they start at the same position.
                    // Make sure we don't try to print them multiple times.
                    $comment_start_pos = $item_start_pos;
                }
                if ($skip_removed_node) {
                    if ($is_stmt_list && $this->orig_tokens->have_tag_in_range($pos, $item_start_pos)) {
                        // We'd remove an opening/closing PHP tag.
                        // TODO: Preserve formatting.
                        $this->set_indent_level($orig_indent_level);
                        return null;
                    }
                } else {
                    $result .= $this->orig_tokens->get_token_code($pos, $comment_start_pos, $indent_adjustment);
                }
                if (!empty($delayed_add)) {
                    /** @var Node $delayedAddNode */
                    foreach ($delayed_add as $delayed_add_node) {
                        if ($insert_newline) {
                            $delayed_add_comments = $delayed_add_node->get_comments();
                            if ($delayed_add_comments) {
                                $result .= $this->p_comments($delayed_add_comments) . $this->nl;
                            }
                        }
                        $this->safe_append($result, $this->p($delayed_add_node, self::MAX_PRECEDENCE, self::MAX_PRECEDENCE, true));
                        if ($insert_newline) {
                            $result .= $insert_str . $this->nl;
                        } else {
                            $result .= $insert_str;
                        }
                    }
                    $delayed_add = [];
                }
                if ($comments !== $orig_comments) {
                    if ($comments) {
                        $result .= $this->p_comments($comments) . $this->nl;
                    }
                } else {
                    $result .= $this->orig_tokens->get_token_code($comment_start_pos, $item_start_pos, $indent_adjustment);
                }
                // If we had to remove anything, we have done so now.
                $skip_removed_node = false;
            } elseif ($diff_type === Diff_Elem::TYPE_ADD) {
                if (null === $insert_str) {
                    // We don't have insertion information for this list type
                    return null;
                }
                if (!$arr_item instanceof Node) {
                    // We only support list insertion of nodes.
                    return null;
                }
                // We go multiline if the original code was multiline,
                // or if it's an array item with a comment above it.
                // Match always uses multiline formatting.
                if ($insert_str === ', ' && ($this->is_multiline($orig_nodes) || $arr_item->get_comments() || $parent_node_class === Expr\Match_::class)) {
                    $insert_str = ',';
                    $insert_newline = true;
                }
                if ($before_first_keep_or_replace) {
                    // Will be inserted at the next "replace" or "keep" element
                    $delayed_add[] = $arr_item;
                    continue;
                }
                $item_start_pos = $pos;
                $item_end_pos = $pos - 1;
                $orig_indent_level = $this->indent_level;
                $this->set_indent_level($last_elem_indent_level);
                if ($insert_newline) {
                    $result .= $insert_str . $this->nl;
                    $comments = $arr_item->get_comments();
                    if ($comments) {
                        $result .= $this->p_comments($comments) . $this->nl;
                    }
                } else {
                    $result .= $insert_str;
                }
            } elseif ($diff_type === Diff_Elem::TYPE_REMOVE) {
                if (!$orig_arr_item instanceof Node) {
                    // We only support removal for nodes
                    return null;
                }
                $item_start_pos = $orig_arr_item->get_start_token_pos();
                $item_end_pos = $orig_arr_item->get_end_token_pos();
                \assert($item_start_pos >= 0 && $item_end_pos >= 0);
                // Consider comments part of the node.
                $orig_comments = $orig_arr_item->get_comments();
                if ($orig_comments) {
                    $item_start_pos = $orig_comments[0]->get_start_token_pos();
                }
                if ($i === 0) {
                    // If we're removing from the start, keep the tokens before the node and drop those after it,
                    // instead of the other way around.
                    $result .= $this->orig_tokens->get_token_code($pos, $item_start_pos, $indent_adjustment);
                    $skip_removed_node = true;
                } else if ($is_stmt_list && $this->orig_tokens->have_tag_in_range($pos, $item_start_pos)) {
                    // We'd remove an opening/closing PHP tag.
                    // TODO: Preserve formatting.
                    return null;
                }
                $pos = $item_end_pos + 1;
                continue;
            } else {
                throw new \Exception("Shouldn't happen");
            }
            if (null !== $fixup && $arr_item->get_attribute('origNode') !== $orig_arr_item) {
                $res = $this->p_fixup($fixup, $arr_item, null, $item_start_pos, $item_end_pos);
            } else {
                $res = $this->p($arr_item, self::MAX_PRECEDENCE, self::MAX_PRECEDENCE, true);
            }
            $this->safe_append($result, $res);
            $this->set_indent_level($orig_indent_level);
            $pos = $item_end_pos + 1;
        }
        if ($skip_removed_node) {
            // TODO: Support removing single node.
            return null;
        }
        if (!empty($delayed_add)) {
            if (!isset($this->empty_list_insertion_map[$map_key])) {
                return null;
            }
            [$find_token, $extra_left, $extra_right] = $this->empty_list_insertion_map[$map_key];
            if (null !== $find_token) {
                // For anon classes skip to the class keyword.
                $is_anon_class_args = $map_key === Printable_New_Anon_Class_Node::class . '->args';
                if ($is_anon_class_args) {
                    $insert_pos = $this->orig_tokens->find_right($pos, \T_CLASS) + 1;
                    $result .= $this->orig_tokens->get_token_code($pos, $insert_pos, $indent_adjustment);
                    $pos = $insert_pos;
                }
                // If "new Foo" was used without arguments, we need to convert to "new Foo()".
                if (($map_key === Expr\New_::class . '->args' || $is_anon_class_args) && !$this->orig_tokens->have_token_immediately_after($pos - 1, '(')) {
                    $extra_left = '(';
                    $extra_right = ')';
                } else {
                    $insert_pos = $this->orig_tokens->find_right($pos, $find_token) + 1;
                    $result .= $this->orig_tokens->get_token_code($pos, $insert_pos, $indent_adjustment);
                    $pos = $insert_pos;
                }
            }
            $first = true;
            $result .= $extra_left;
            foreach ($delayed_add as $delayed_add_node) {
                if (!$first) {
                    $result .= $insert_str;
                    if ($insert_newline) {
                        $result .= $this->nl;
                    }
                }
                $result .= $this->p($delayed_add_node, self::MAX_PRECEDENCE, self::MAX_PRECEDENCE, true);
                $first = false;
            }
            $result .= $extra_right === "\n" ? $this->nl : $extra_right;
        }
        return $result;
    }
    /**
     * Print node with fixups.
     *
     * Fixups here refer to the addition of extra parentheses, braces or other characters, that
     * are required to preserve program semantics in a certain context (e.g. to maintain precedence
     * or because only certain expressions are allowed in certain places).
     *
     * @param int $fixup Fixup type
     * @param Node $subNode Subnode to print
     * @param string|null $parentClass Class of parent node
     * @param int $subStartPos Original start pos of subnode
     * @param int $subEndPos Original end pos of subnode
     *
     * @return string Result of fixed-up print of subnode
     */
    protected function p_fixup(int $fixup, Node $sub_node, ?string $parent_class, int $sub_start_pos, int $sub_end_pos): string
    {
        switch ($fixup) {
            case self::FIXUP_PREC_LEFT:
                // We use a conservative approximation where lhsPrecedence == precedence.
                if (!$this->orig_tokens->have_parens($sub_start_pos, $sub_end_pos)) {
                    $precedence = $this->precedence_map[$parent_class][1];
                    return $this->p($sub_node, $precedence, $precedence);
                }
                break;
            case self::FIXUP_PREC_RIGHT:
                if (!$this->orig_tokens->have_parens($sub_start_pos, $sub_end_pos)) {
                    $precedence = $this->precedence_map[$parent_class][2];
                    return $this->p($sub_node, $precedence, $precedence);
                }
                break;
            case self::FIXUP_PREC_UNARY:
                if (!$this->orig_tokens->have_parens($sub_start_pos, $sub_end_pos)) {
                    $precedence = $this->precedence_map[$parent_class][0];
                    return $this->p($sub_node, $precedence, $precedence);
                }
                break;
            case self::FIXUP_CALL_LHS:
                if ($this->call_lhs_requires_parens($sub_node) && !$this->orig_tokens->have_parens($sub_start_pos, $sub_end_pos)) {
                    return '(' . $this->p($sub_node) . ')';
                }
                break;
            case self::FIXUP_DEREF_LHS:
                if ($this->dereference_lhs_requires_parens($sub_node) && !$this->orig_tokens->have_parens($sub_start_pos, $sub_end_pos)) {
                    return '(' . $this->p($sub_node) . ')';
                }
                break;
            case self::FIXUP_STATIC_DEREF_LHS:
                if ($this->static_dereference_lhs_requires_parens($sub_node) && !$this->orig_tokens->have_parens($sub_start_pos, $sub_end_pos)) {
                    return '(' . $this->p($sub_node) . ')';
                }
                break;
            case self::FIXUP_NEW:
                if ($this->new_operand_requires_parens($sub_node) && !$this->orig_tokens->have_parens($sub_start_pos, $sub_end_pos)) {
                    return '(' . $this->p($sub_node) . ')';
                }
                break;
            case self::FIXUP_BRACED_NAME:
            case self::FIXUP_VAR_BRACED_NAME:
                if ($sub_node instanceof Expr && !$this->orig_tokens->have_braces($sub_start_pos, $sub_end_pos)) {
                    return ($fixup === self::FIXUP_VAR_BRACED_NAME ? '$' : '') . '{' . $this->p($sub_node) . '}';
                }
                break;
            case self::FIXUP_ENCAPSED:
                if (!$sub_node instanceof Node\Interpolated_String_Part && !$this->orig_tokens->have_braces($sub_start_pos, $sub_end_pos)) {
                    return '{' . $this->p($sub_node) . '}';
                }
                break;
            default:
                throw new \Exception('Cannot happen');
        }
        // Nothing special to do
        return $this->p($sub_node);
    }
    /**
     * Appends to a string, ensuring whitespace between label characters.
     *
     * Example: "echo" and "$x" result in "echo$x", but "echo" and "x" result in "echo x".
     * Without safeAppend the result would be "echox", which does not preserve semantics.
     */
    protected function safe_append(string &$str, string $append): void
    {
        if ($str === '') {
            $str = $append;
            return;
        }
        if ($append === '') {
            return;
        }
        if (!$this->label_char_map[$append[0]] || !$this->label_char_map[$str[\strlen($str) - 1]]) {
            $str .= $append;
        } else {
            $str .= ' ' . $append;
        }
    }
    /**
     * Determines whether the LHS of a call must be wrapped in parenthesis.
     *
     * @param Node $node LHS of a call
     *
     * @return bool Whether parentheses are required
     */
    protected function call_lhs_requires_parens(Node $node): bool
    {
        if ($node instanceof Expr\New_) {
            return !$this->php_version->supports_new_dereference_without_parentheses();
        }
        return !($node instanceof Node\Name || $node instanceof Expr\Variable || $node instanceof Expr\Array_Dim_Fetch || $node instanceof Expr\Func_Call || $node instanceof Expr\Method_Call || $node instanceof Expr\Nullsafe_Method_Call || $node instanceof Expr\Static_Call || $node instanceof Expr\Array_);
    }
    /**
     * Determines whether the LHS of an array/object operation must be wrapped in parentheses.
     *
     * @param Node $node LHS of dereferencing operation
     *
     * @return bool Whether parentheses are required
     */
    protected function dereference_lhs_requires_parens(Node $node): bool
    {
        // A constant can occur on the LHS of an array/object deref, but not a static deref.
        return $this->static_dereference_lhs_requires_parens($node) && !$node instanceof Expr\Const_Fetch;
    }
    /**
     * Determines whether the LHS of a static operation must be wrapped in parentheses.
     *
     * @param Node $node LHS of dereferencing operation
     *
     * @return bool Whether parentheses are required
     */
    protected function static_dereference_lhs_requires_parens(Node $node): bool
    {
        if ($node instanceof Expr\New_) {
            return !$this->php_version->supports_new_dereference_without_parentheses();
        }
        return !($node instanceof Expr\Variable || $node instanceof Node\Name || $node instanceof Expr\Array_Dim_Fetch || $node instanceof Expr\Property_Fetch || $node instanceof Expr\Nullsafe_Property_Fetch || $node instanceof Expr\Static_Property_Fetch || $node instanceof Expr\Func_Call || $node instanceof Expr\Method_Call || $node instanceof Expr\Nullsafe_Method_Call || $node instanceof Expr\Static_Call || $node instanceof Expr\Array_ || $node instanceof Scalar\String_ || $node instanceof Expr\Class_Const_Fetch);
    }
    /**
     * Determines whether an expression used in "new" or "instanceof" requires parentheses.
     *
     * @param Node $node New or instanceof operand
     *
     * @return bool Whether parentheses are required
     */
    protected function new_operand_requires_parens(Node $node): bool
    {
        if ($node instanceof Node\Name || $node instanceof Expr\Variable) {
            return false;
        }
        if ($node instanceof Expr\Array_Dim_Fetch || $node instanceof Expr\Property_Fetch || $node instanceof Expr\Nullsafe_Property_Fetch) {
            return $this->new_operand_requires_parens($node->var);
        }
        if ($node instanceof Expr\Static_Property_Fetch) {
            return $this->new_operand_requires_parens($node->class);
        }
        return true;
    }
    /**
     * Print modifiers, including trailing whitespace.
     *
     * @param int $modifiers Modifier mask to print
     *
     * @return string Printed modifiers
     */
    protected function p_modifiers(int $modifiers): string
    {
        return ($modifiers & Modifiers::FINAL ? 'final ' : '') . ($modifiers & Modifiers::ABSTRACT ? 'abstract ' : '') . ($modifiers & Modifiers::PUBLIC ? 'public ' : '') . ($modifiers & Modifiers::PROTECTED ? 'protected ' : '') . ($modifiers & Modifiers::PRIVATE ? 'private ' : '') . ($modifiers & Modifiers::PUBLIC_SET ? 'public(set) ' : '') . ($modifiers & Modifiers::PROTECTED_SET ? 'protected(set) ' : '') . ($modifiers & Modifiers::PRIVATE_SET ? 'private(set) ' : '') . ($modifiers & Modifiers::STATIC ? 'static ' : '') . ($modifiers & Modifiers::READONLY ? 'readonly ' : '');
    }
    protected function p_static(bool $static): string
    {
        return $static ? 'static ' : '';
    }
    /**
     * Determine whether a list of nodes uses multiline formatting.
     *
     * @param (Node|null)[] $nodes Node list
     *
     * @return bool Whether multiline formatting is used
     */
    protected function is_multiline(array $nodes): bool
    {
        if (\count($nodes) < 2) {
            return false;
        }
        $pos = -1;
        foreach ($nodes as $node) {
            if (null === $node) {
                continue;
            }
            $end_pos = $node->get_end_token_pos() + 1;
            if ($pos >= 0) {
                $text = $this->orig_tokens->get_token_code($pos, $end_pos, 0);
                if (false === strpos($text, "\n")) {
                    // We require that a newline is present between *every* item. If the formatting
                    // is inconsistent, with only some items having newlines, we don't consider it
                    // as multiline
                    return false;
                }
            }
            $pos = $end_pos;
        }
        return true;
    }
    /**
     * Lazily initializes label char map.
     *
     * The label char map determines whether a certain character may occur in a label.
     */
    protected function initialize_label_char_map(): void
    {
        if (isset($this->label_char_map)) {
            return;
        }
        $this->label_char_map = [];
        for ($i = 0; $i < 256; $i++) {
            $chr = chr($i);
            $this->label_char_map[$chr] = $i >= 0x80 || ctype_alnum($chr);
        }
        if ($this->php_version->allows_del_in_identifiers()) {
            $this->label_char_map[""] = true;
        }
    }
    /**
     * Lazily initializes node list differ.
     *
     * The node list differ is used to determine differences between two array subnodes.
     */
    protected function initialize_node_list_differ(): void
    {
        if (isset($this->node_list_differ)) {
            return;
        }
        $this->node_list_differ = new Internal\Differ(function ($a, $b): bool {
            if ($a instanceof Node && $b instanceof Node) {
                return $a === $b->get_attribute('origNode');
            }
            // Can happen for array destructuring
            return $a === null && $b === null;
        });
    }
    /**
     * Lazily initializes fixup map.
     *
     * The fixup map is used to determine whether a certain subnode of a certain node may require
     * some kind of "fixup" operation, e.g. the addition of parenthesis or braces.
     */
    protected function initialize_fixup_map(): void
    {
        if (isset($this->fixup_map)) {
            return;
        }
        $this->fixup_map = [Expr\Instanceof_::class => ['expr' => self::FIXUP_PREC_UNARY, 'class' => self::FIXUP_NEW], Expr\Ternary::class => ['cond' => self::FIXUP_PREC_LEFT, 'else' => self::FIXUP_PREC_RIGHT], Expr\Yield_::class => ['value' => self::FIXUP_PREC_UNARY], Expr\Func_Call::class => ['name' => self::FIXUP_CALL_LHS], Expr\Static_Call::class => ['class' => self::FIXUP_STATIC_DEREF_LHS], Expr\Array_Dim_Fetch::class => ['var' => self::FIXUP_DEREF_LHS], Expr\Class_Const_Fetch::class => ['class' => self::FIXUP_STATIC_DEREF_LHS, 'name' => self::FIXUP_BRACED_NAME], Expr\New_::class => ['class' => self::FIXUP_NEW], Expr\Method_Call::class => ['var' => self::FIXUP_DEREF_LHS, 'name' => self::FIXUP_BRACED_NAME], Expr\Nullsafe_Method_Call::class => ['var' => self::FIXUP_DEREF_LHS, 'name' => self::FIXUP_BRACED_NAME], Expr\Static_Property_Fetch::class => ['class' => self::FIXUP_STATIC_DEREF_LHS, 'name' => self::FIXUP_VAR_BRACED_NAME], Expr\Property_Fetch::class => ['var' => self::FIXUP_DEREF_LHS, 'name' => self::FIXUP_BRACED_NAME], Expr\Nullsafe_Property_Fetch::class => ['var' => self::FIXUP_DEREF_LHS, 'name' => self::FIXUP_BRACED_NAME], Scalar\Interpolated_String::class => ['parts' => self::FIXUP_ENCAPSED]];
        $binary_ops = [Binary_Op\Pow::class, Binary_Op\Mul::class, Binary_Op\Div::class, Binary_Op\Mod::class, Binary_Op\Plus::class, Binary_Op\Minus::class, Binary_Op\Concat::class, Binary_Op\Shift_Left::class, Binary_Op\Shift_Right::class, Binary_Op\Smaller::class, Binary_Op\Smaller_Or_Equal::class, Binary_Op\Greater::class, Binary_Op\Greater_Or_Equal::class, Binary_Op\Equal::class, Binary_Op\Not_Equal::class, Binary_Op\Identical::class, Binary_Op\Not_Identical::class, Binary_Op\Spaceship::class, Binary_Op\Bitwise_And::class, Binary_Op\Bitwise_Xor::class, Binary_Op\Bitwise_Or::class, Binary_Op\Boolean_And::class, Binary_Op\Boolean_Or::class, Binary_Op\Coalesce::class, Binary_Op\Logical_And::class, Binary_Op\Logical_Xor::class, Binary_Op\Logical_Or::class, Binary_Op\Pipe::class];
        foreach ($binary_ops as $binary_op) {
            $this->fixup_map[$binary_op] = ['left' => self::FIXUP_PREC_LEFT, 'right' => self::FIXUP_PREC_RIGHT];
        }
        $prefix_ops = [Expr\Clone_::class, Expr\Bitwise_Not::class, Expr\Boolean_Not::class, Expr\Unary_Plus::class, Expr\Unary_Minus::class, Cast\Int_::class, Cast\Double::class, Cast\String_::class, Cast\Array_::class, Cast\Object_::class, Cast\Bool_::class, Cast\Unset_::class, Expr\Error_Suppress::class, Expr\Yield_From::class, Expr\Print_::class, Expr\Include_::class, Expr\Assign::class, Expr\Assign_Ref::class, Assign_Op\Plus::class, Assign_Op\Minus::class, Assign_Op\Mul::class, Assign_Op\Div::class, Assign_Op\Concat::class, Assign_Op\Mod::class, Assign_Op\Bitwise_And::class, Assign_Op\Bitwise_Or::class, Assign_Op\Bitwise_Xor::class, Assign_Op\Shift_Left::class, Assign_Op\Shift_Right::class, Assign_Op\Pow::class, Assign_Op\Coalesce::class, Expr\Arrow_Function::class, Expr\Throw_::class];
        foreach ($prefix_ops as $prefix_op) {
            $this->fixup_map[$prefix_op] = ['expr' => self::FIXUP_PREC_UNARY];
        }
    }
    /**
     * Lazily initializes the removal map.
     *
     * The removal map is used to determine which additional tokens should be removed when a
     * certain node is replaced by null.
     */
    protected function initialize_removal_map(): void
    {
        if (isset($this->removal_map)) {
            return;
        }
        $strip_both = ['left' => \T_WHITESPACE, 'right' => \T_WHITESPACE];
        $strip_left = ['left' => \T_WHITESPACE];
        $strip_right = ['right' => \T_WHITESPACE];
        $strip_double_arrow = ['right' => \T_DOUBLE_ARROW];
        $strip_colon = ['left' => ':'];
        $strip_equals = ['left' => '='];
        $this->removal_map = ['Expr_ArrayDimFetch->dim' => $strip_both, 'ArrayItem->key' => $strip_double_arrow, 'Expr_ArrowFunction->returnType' => $strip_colon, 'Expr_Closure->returnType' => $strip_colon, 'Expr_Exit->expr' => $strip_both, 'Expr_Ternary->if' => $strip_both, 'Expr_Yield->key' => $strip_double_arrow, 'Expr_Yield->value' => $strip_both, 'Param->type' => $strip_right, 'Param->default' => $strip_equals, 'Stmt_Break->num' => $strip_both, 'Stmt_Catch->var' => $strip_left, 'Stmt_ClassConst->type' => $strip_right, 'Stmt_ClassMethod->returnType' => $strip_colon, 'Stmt_Class->extends' => ['left' => \T_EXTENDS], 'Stmt_Enum->scalarType' => $strip_colon, 'Stmt_EnumCase->expr' => $strip_equals, 'Expr_PrintableNewAnonClass->extends' => ['left' => \T_EXTENDS], 'Stmt_Continue->num' => $strip_both, 'Stmt_Foreach->keyVar' => $strip_double_arrow, 'Stmt_Function->returnType' => $strip_colon, 'Stmt_If->else' => $strip_left, 'Stmt_Namespace->name' => $strip_left, 'Stmt_Property->type' => $strip_right, 'PropertyItem->default' => $strip_equals, 'Stmt_Return->expr' => $strip_both, 'Stmt_StaticVar->default' => $strip_equals, 'Stmt_TraitUseAdaptation_Alias->newName' => $strip_left, 'Stmt_TryCatch->finally' => $strip_left];
    }
    protected function initialize_insertion_map(): void
    {
        if (isset($this->insertion_map)) {
            return;
        }
        // TODO: "yield" where both key and value are inserted doesn't work
        // [$find, $beforeToken, $extraLeft, $extraRight]
        $this->insertion_map = [
            'Expr_ArrayDimFetch->dim' => ['[', false, null, null],
            'ArrayItem->key' => [null, false, null, ' => '],
            'Expr_ArrowFunction->returnType' => [')', false, ': ', null],
            'Expr_Closure->returnType' => [')', false, ': ', null],
            'Expr_Ternary->if' => ['?', false, ' ', ' '],
            'Expr_Yield->key' => [\T_YIELD, false, null, ' => '],
            'Expr_Yield->value' => [\T_YIELD, false, ' ', null],
            'Param->type' => [null, false, null, ' '],
            'Param->default' => [null, false, ' = ', null],
            'Stmt_Break->num' => [\T_BREAK, false, ' ', null],
            'Stmt_Catch->var' => [null, false, ' ', null],
            'Stmt_ClassMethod->returnType' => [')', false, ': ', null],
            'Stmt_ClassConst->type' => [\T_CONST, false, ' ', null],
            'Stmt_Class->extends' => [null, false, ' extends ', null],
            'Stmt_Enum->scalarType' => [null, false, ' : ', null],
            'Stmt_EnumCase->expr' => [null, false, ' = ', null],
            'Expr_PrintableNewAnonClass->extends' => [null, false, ' extends ', null],
            'Stmt_Continue->num' => [\T_CONTINUE, false, ' ', null],
            'Stmt_Foreach->keyVar' => [\T_AS, false, null, ' => '],
            'Stmt_Function->returnType' => [')', false, ': ', null],
            'Stmt_If->else' => [null, false, ' ', null],
            'Stmt_Namespace->name' => [\T_NAMESPACE, false, ' ', null],
            'Stmt_Property->type' => [\T_VARIABLE, true, null, ' '],
            'PropertyItem->default' => [null, false, ' = ', null],
            'Stmt_Return->expr' => [\T_RETURN, false, ' ', null],
            'Stmt_StaticVar->default' => [null, false, ' = ', null],
            //'Stmt_TraitUseAdaptation_Alias->newName' => [T_AS, false, ' ', null], // TODO
            'Stmt_TryCatch->finally' => [null, false, ' ', null],
        ];
    }
    protected function initialize_list_insertion_map(): void
    {
        if (isset($this->list_insertion_map)) {
            return;
        }
        $this->list_insertion_map = [
            // special
            //'Expr_ShellExec->parts' => '', // TODO These need to be treated more carefully
            //'Scalar_InterpolatedString->parts' => '',
            Stmt\Catch_::class . '->types' => '|',
            Union_Type::class . '->types' => '|',
            Intersection_Type::class . '->types' => '&',
            Stmt\If_::class . '->elseifs' => ' ',
            Stmt\Try_Catch::class . '->catches' => ' ',
            // comma-separated lists
            Expr\Array_::class . '->items' => ', ',
            Expr\Arrow_Function::class . '->params' => ', ',
            Expr\Closure::class . '->params' => ', ',
            Expr\Closure::class . '->uses' => ', ',
            Expr\Func_Call::class . '->args' => ', ',
            Expr\Isset_::class . '->vars' => ', ',
            Expr\List_::class . '->items' => ', ',
            Expr\Method_Call::class . '->args' => ', ',
            Expr\Nullsafe_Method_Call::class . '->args' => ', ',
            Expr\New_::class . '->args' => ', ',
            Printable_New_Anon_Class_Node::class . '->args' => ', ',
            Expr\Static_Call::class . '->args' => ', ',
            Stmt\Class_Const::class . '->consts' => ', ',
            Stmt\Class_Method::class . '->params' => ', ',
            Stmt\Class_::class . '->implements' => ', ',
            Stmt\Enum_::class . '->implements' => ', ',
            Printable_New_Anon_Class_Node::class . '->implements' => ', ',
            Stmt\Const_::class . '->consts' => ', ',
            Stmt\Declare_::class . '->declares' => ', ',
            Stmt\Echo_::class . '->exprs' => ', ',
            Stmt\For_::class . '->init' => ', ',
            Stmt\For_::class . '->cond' => ', ',
            Stmt\For_::class . '->loop' => ', ',
            Stmt\Function_::class . '->params' => ', ',
            Stmt\Global_::class . '->vars' => ', ',
            Stmt\Group_Use::class . '->uses' => ', ',
            Stmt\Interface_::class . '->extends' => ', ',
            Expr\Match_::class . '->arms' => ', ',
            Stmt\Property::class . '->props' => ', ',
            Stmt\Static_Var::class . '->vars' => ', ',
            Stmt\Trait_Use::class . '->traits' => ', ',
            Stmt\Trait_Use_Adaptation\Precedence::class . '->insteadof' => ', ',
            Stmt\Unset_::class . '->vars' => ', ',
            Stmt\Use_Use::class . '->uses' => ', ',
            Match_Arm::class . '->conds' => ', ',
            Attribute_Group::class . '->attrs' => ', ',
            Property_Hook::class . '->params' => ', ',
            // statement lists
            Expr\Closure::class . '->stmts' => "\n",
            Stmt\Case_::class . '->stmts' => "\n",
            Stmt\Catch_::class . '->stmts' => "\n",
            Stmt\Class_::class . '->stmts' => "\n",
            Stmt\Enum_::class . '->stmts' => "\n",
            Printable_New_Anon_Class_Node::class . '->stmts' => "\n",
            Stmt\Interface_::class . '->stmts' => "\n",
            Stmt\Trait_::class . '->stmts' => "\n",
            Stmt\Class_Method::class . '->stmts' => "\n",
            Stmt\Declare_::class . '->stmts' => "\n",
            Stmt\Do_::class . '->stmts' => "\n",
            Stmt\Else_If_::class . '->stmts' => "\n",
            Stmt\Else_::class . '->stmts' => "\n",
            Stmt\Finally_::class . '->stmts' => "\n",
            Stmt\Foreach_::class . '->stmts' => "\n",
            Stmt\For_::class . '->stmts' => "\n",
            Stmt\Function_::class . '->stmts' => "\n",
            Stmt\If_::class . '->stmts' => "\n",
            Stmt\Namespace_::class . '->stmts' => "\n",
            Stmt\Block::class . '->stmts' => "\n",
            // Attribute groups
            Stmt\Class_::class . '->attrGroups' => "\n",
            Stmt\Enum_::class . '->attrGroups' => "\n",
            Stmt\Enum_Case::class . '->attrGroups' => "\n",
            Stmt\Interface_::class . '->attrGroups' => "\n",
            Stmt\Trait_::class . '->attrGroups' => "\n",
            Stmt\Function_::class . '->attrGroups' => "\n",
            Stmt\Class_Method::class . '->attrGroups' => "\n",
            Stmt\Class_Const::class . '->attrGroups' => "\n",
            Stmt\Property::class . '->attrGroups' => "\n",
            Printable_New_Anon_Class_Node::class . '->attrGroups' => ' ',
            Expr\Closure::class . '->attrGroups' => ' ',
            Expr\Arrow_Function::class . '->attrGroups' => ' ',
            Param::class . '->attrGroups' => ' ',
            Property_Hook::class . '->attrGroups' => ' ',
            Stmt\Switch_::class . '->cases' => "\n",
            Stmt\Trait_Use::class . '->adaptations' => "\n",
            Stmt\Try_Catch::class . '->stmts' => "\n",
            Stmt\While_::class . '->stmts' => "\n",
            Property_Hook::class . '->body' => "\n",
            Stmt\Property::class . '->hooks' => "\n",
            Param::class . '->hooks' => "\n",
            // dummy for top-level context
            'File->stmts' => "\n",
        ];
    }
    protected function initialize_empty_list_insertion_map(): void
    {
        if (isset($this->empty_list_insertion_map)) {
            return;
        }
        // TODO Insertion into empty statement lists.
        // [$find, $extraLeft, $extraRight]
        $this->empty_list_insertion_map = [Expr\Arrow_Function::class . '->params' => ['(', '', ''], Expr\Closure::class . '->uses' => [')', ' use (', ')'], Expr\Closure::class . '->params' => ['(', '', ''], Expr\Func_Call::class . '->args' => ['(', '', ''], Expr\Method_Call::class . '->args' => ['(', '', ''], Expr\Nullsafe_Method_Call::class . '->args' => ['(', '', ''], Expr\New_::class . '->args' => ['(', '', ''], Printable_New_Anon_Class_Node::class . '->args' => ['(', '', ''], Printable_New_Anon_Class_Node::class . '->implements' => [null, ' implements ', ''], Expr\Static_Call::class . '->args' => ['(', '', ''], Stmt\Class_::class . '->implements' => [null, ' implements ', ''], Stmt\Enum_::class . '->implements' => [null, ' implements ', ''], Stmt\Class_Method::class . '->params' => ['(', '', ''], Stmt\Interface_::class . '->extends' => [null, ' extends ', ''], Stmt\Function_::class . '->params' => ['(', '', ''], Stmt\Interface_::class . '->attrGroups' => [null, '', "\n"], Stmt\Class_::class . '->attrGroups' => [null, '', "\n"], Stmt\Class_Const::class . '->attrGroups' => [null, '', "\n"], Stmt\Class_Method::class . '->attrGroups' => [null, '', "\n"], Stmt\Function_::class . '->attrGroups' => [null, '', "\n"], Stmt\Property::class . '->attrGroups' => [null, '', "\n"], Stmt\Trait_::class . '->attrGroups' => [null, '', "\n"], Expr\Arrow_Function::class . '->attrGroups' => [null, '', ' '], Expr\Closure::class . '->attrGroups' => [null, '', ' '], Stmt\Const_::class . '->attrGroups' => [null, '', "\n"], Printable_New_Anon_Class_Node::class . '->attrGroups' => [\T_NEW, ' ', '']];
    }
    protected function initialize_modifier_change_map(): void
    {
        if (isset($this->modifier_change_map)) {
            return;
        }
        $this->modifier_change_map = [Stmt\Class_Const::class . '->flags' => ['pModifiers', \T_WHITESPACE, \T_CONST], Stmt\Class_Method::class . '->flags' => ['pModifiers', \T_WHITESPACE, \T_FUNCTION], Stmt\Class_::class . '->flags' => ['pModifiers', \T_WHITESPACE, \T_CLASS], Stmt\Property::class . '->flags' => ['pModifiers', \T_WHITESPACE, \T_VARIABLE], Printable_New_Anon_Class_Node::class . '->flags' => ['pModifiers', \T_NEW, \T_CLASS], Param::class . '->flags' => ['pModifiers', \T_WHITESPACE, \T_VARIABLE], Property_Hook::class . '->flags' => ['pModifiers', \T_WHITESPACE, \T_STRING], Expr\Closure::class . '->static' => ['pStatic', \T_WHITESPACE, \T_FUNCTION], Expr\Arrow_Function::class . '->static' => ['pStatic', \T_WHITESPACE, \T_FN]];
        // List of integer subnodes that are not modifiers:
        // Expr_Include->type
        // Stmt_GroupUse->type
        // Stmt_Use->type
        // UseItem->type
    }
}