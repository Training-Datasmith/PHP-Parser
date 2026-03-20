<?php

declare (strict_types=1);
namespace Php_Parser;

/*
 * This parser is based on a skeleton written by Moriyoshi Koizumi, which in
 * turn is based on work by Masato Bito.
 */
use Php_Parser\Node\Arg;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Array_;
use Php_Parser\Node\Expr\Cast\Double;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Interpolated_String_Part;
use Php_Parser\Node\Name;
use Php_Parser\Node\Param;
use Php_Parser\Node\Property_Hook;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Scalar\Interpolated_String;
use Php_Parser\Node\Scalar\String_;
use Php_Parser\Node\Stmt;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Class_Const;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Const_;
use Php_Parser\Node\Stmt\Else_;
use Php_Parser\Node\Stmt\Else_If_;
use Php_Parser\Node\Stmt\Enum_;
use Php_Parser\Node\Stmt\Interface_;
use Php_Parser\Node\Stmt\Namespace_;
use Php_Parser\Node\Stmt\Nop;
use Php_Parser\Node\Stmt\Property;
use Php_Parser\Node\Stmt\Try_Catch;
use Php_Parser\Node\Use_Item;
use Php_Parser\Node_Visitor\Comment_Annotating_Visitor;
abstract class Parser_Abstract implements Parser
{
    private const SYMBOL_NONE = -1;
    /** @var Lexer Lexer that is used when parsing */
    protected Lexer $lexer;
    /** @var PhpVersion PHP version to target on a best-effort basis */
    protected Php_Version $php_version;
    /*
     * The following members will be filled with generated parsing data:
     */
    /** @var int Size of $tokenToSymbol map */
    protected int $token_to_symbol_map_size;
    /** @var int Size of $action table */
    protected int $action_table_size;
    /** @var int Size of $goto table */
    protected int $goto_table_size;
    /** @var int Symbol number signifying an invalid token */
    protected int $invalid_symbol;
    /** @var int Symbol number of error recovery token */
    protected int $error_symbol;
    /** @var int Action number signifying default action */
    protected int $default_action;
    /** @var int Rule number signifying that an unexpected token was encountered */
    protected int $unexpected_token_rule;
    protected int $YY2TBLSTATE;
    /** @var int Number of non-leaf states */
    protected int $num_non_leaf_states;
    /** @var int[] Map of PHP token IDs to internal symbols */
    protected array $php_token_to_symbol;
    /** @var array<int, bool> Map of PHP token IDs to drop */
    protected array $drop_tokens;
    /** @var int[] Map of external symbols (static::T_*) to internal symbols */
    protected array $token_to_symbol;
    /** @var string[] Map of symbols to their names */
    protected array $symbol_to_name;
    /** @var array<int, string> Names of the production rules (only necessary for debugging) */
    protected array $productions;
    /** @var int[] Map of states to a displacement into the $action table. The corresponding action for this
     *             state/symbol pair is $action[$actionBase[$state] + $symbol]. If $actionBase[$state] is 0, the
     *             action is defaulted, i.e. $actionDefault[$state] should be used instead. */
    protected array $action_base;
    /** @var int[] Table of actions. Indexed according to $actionBase comment. */
    protected array $action;
    /** @var int[] Table indexed analogously to $action. If $actionCheck[$actionBase[$state] + $symbol] != $symbol
     *             then the action is defaulted, i.e. $actionDefault[$state] should be used instead. */
    protected array $action_check;
    /** @var int[] Map of states to their default action */
    protected array $action_default;
    /** @var callable[] Semantic action callbacks */
    protected array $reduce_callbacks;
    /** @var int[] Map of non-terminals to a displacement into the $goto table. The corresponding goto state for this
     *             non-terminal/state pair is $goto[$gotoBase[$nonTerminal] + $state] (unless defaulted) */
    protected array $goto_base;
    /** @var int[] Table of states to goto after reduction. Indexed according to $gotoBase comment. */
    protected array $goto;
    /** @var int[] Table indexed analogously to $goto. If $gotoCheck[$gotoBase[$nonTerminal] + $state] != $nonTerminal
     *             then the goto state is defaulted, i.e. $gotoDefault[$nonTerminal] should be used. */
    protected array $goto_check;
    /** @var int[] Map of non-terminals to the default state to goto after their reduction */
    protected array $goto_default;
    /** @var int[] Map of rules to the non-terminal on their left-hand side, i.e. the non-terminal to use for
     *             determining the state to goto after reduction. */
    protected array $rule_to_non_terminal;
    /** @var int[] Map of rules to the length of their right-hand side, which is the number of elements that have to
     *             be popped from the stack(s) on reduction. */
    protected array $rule_to_length;
    /*
     * The following members are part of the parser state:
     */
    /** @var mixed Temporary value containing the result of last semantic action (reduction) */
    protected $sem_value;
    /** @var mixed[] Semantic value stack (contains values of tokens and semantic action results) */
    protected array $sem_stack;
    /** @var int[] Token start position stack */
    protected array $token_start_stack;
    /** @var int[] Token end position stack */
    protected array $token_end_stack;
    /** @var ErrorHandler Error handler */
    protected Error_Handler $error_handler;
    /** @var int Error state, used to avoid error floods */
    protected int $error_state;
    /** @var \SplObjectStorage<Array_, null>|null Array nodes created during parsing, for postprocessing of empty elements. */
    protected ?\Spl_Object_Storage $created_arrays = null;
    /** @var \SplObjectStorage<Expr\ArrowFunction, null>|null
     *       Arrow functions that are wrapped in parentheses, to enforce the pipe operator parentheses requirements.
     */
    protected ?\Spl_Object_Storage $parenthesized_arrow_functions = null;
    /** @var Token[] Tokens for the current parse */
    protected array $tokens;
    /** @var int Current position in token array */
    protected int $token_pos;
    /**
     * Initialize $reduceCallbacks map.
     */
    abstract protected function init_reduce_callbacks(): void;
    /**
     * Creates a parser instance.
     *
     * Options:
     *  * phpVersion: ?PhpVersion,
     *
     * @param Lexer $lexer A lexer
     * @param PhpVersion $phpVersion PHP version to target, defaults to latest supported. This
     *                               option is best-effort: Even if specified, parsing will generally assume the latest
     *                               supported version and only adjust behavior in minor ways, for example by omitting
     *                               errors in older versions and interpreting type hints as a name or identifier depending
     *                               on version.
     */
    public function __construct(Lexer $lexer, ?Php_Version $php_version = null)
    {
        $this->lexer = $lexer;
        $this->php_version = $php_version ?? Php_Version::get_newest_supported();
        $this->init_reduce_callbacks();
        $this->php_token_to_symbol = $this->create_token_map();
        $this->drop_tokens = array_fill_keys([\T_WHITESPACE, \T_OPEN_TAG, \T_COMMENT, \T_DOC_COMMENT, \T_BAD_CHARACTER], true);
    }
    /**
     * Parses PHP code into a node tree.
     *
     * If a non-throwing error handler is used, the parser will continue parsing after an error
     * occurred and attempt to build a partial AST.
     *
     * @param string $code The source code to parse
     * @param ErrorHandler|null $errorHandler Error handler to use for lexer/parser errors, defaults
     *                                        to ErrorHandler\Throwing.
     *
     * @return Node\Stmt[]|null Array of statements (or null non-throwing error handler is used and
     *                          the parser was unable to recover from an error).
     */
    public function parse(string $code, ?Error_Handler $error_handler = null): ?array
    {
        $this->error_handler = $error_handler ?: new Error_Handler\Throwing();
        $this->created_arrays = new \Spl_Object_Storage();
        $this->parenthesized_arrow_functions = new \Spl_Object_Storage();
        $this->tokens = $this->lexer->tokenize($code, $this->error_handler);
        $result = $this->do_parse();
        // Report errors for any empty elements used inside arrays. This is delayed until after the main parse,
        // because we don't know a priori whether a given array expression will be used in a destructuring context
        // or not.
        foreach ($this->created_arrays as $node) {
            foreach ($node->items as $item) {
                if ($item->value instanceof Expr\Error) {
                    $this->error_handler->handle_error(new Error('Cannot use empty array elements in arrays', $item->get_attributes()));
                }
            }
        }
        // Clear out some of the interior state, so we don't hold onto unnecessary
        // memory between uses of the parser
        $this->token_start_stack = [];
        $this->token_end_stack = [];
        $this->sem_stack = [];
        $this->sem_value = null;
        $this->created_arrays = null;
        $this->parenthesized_arrow_functions = null;
        if ($result !== null) {
            $traverser = new Node_Traverser(new Comment_Annotating_Visitor($this->tokens));
            $traverser->traverse($result);
        }
        return $result;
    }
    public function get_tokens(): array
    {
        return $this->tokens;
    }
    /** @return Stmt[]|null */
    protected function do_parse(): ?array
    {
        // We start off with no lookahead-token
        $symbol = self::SYMBOL_NONE;
        $token_value = null;
        $this->token_pos = -1;
        // Keep stack of start and end attributes
        $this->token_start_stack = [];
        $this->token_end_stack = [0];
        // Start off in the initial state and keep a stack of previous states
        $state = 0;
        $state_stack = [$state];
        // Semantic value stack (contains values of tokens and semantic action results)
        $this->sem_stack = [];
        // Current position in the stack(s)
        $stack_pos = 0;
        $this->error_state = 0;
        for (;;) {
            //$this->traceNewState($state, $symbol);
            if ($this->action_base[$state] === 0) {
                $rule = $this->action_default[$state];
            } else {
                if ($symbol === self::SYMBOL_NONE) {
                    do {
                        $token = $this->tokens[++$this->token_pos];
                        $token_id = $token->id;
                    } while (isset($this->drop_tokens[$token_id]));
                    // Map the lexer token id to the internally used symbols.
                    $token_value = $token->text;
                    if (!isset($this->php_token_to_symbol[$token_id])) {
                        throw new \RangeException(sprintf('The lexer returned an invalid token (id=%d, value=%s)', $token_id, $token_value));
                    }
                    $symbol = $this->php_token_to_symbol[$token_id];
                    //$this->traceRead($symbol);
                }
                $idx = $this->action_base[$state] + $symbol;
                if (($idx >= 0 && $idx < $this->action_table_size && $this->action_check[$idx] === $symbol || $state < $this->YY2TBLSTATE && ($idx = $this->action_base[$state + $this->num_non_leaf_states] + $symbol) >= 0 && $idx < $this->action_table_size && $this->action_check[$idx] === $symbol) && ($action = $this->action[$idx]) !== $this->default_action) {
                    /*
                     * >= numNonLeafStates: shift and reduce
                     * > 0: shift
                     * = 0: accept
                     * < 0: reduce
                     * = -YYUNEXPECTED: error
                     */
                    if ($action > 0) {
                        /* shift */
                        //$this->traceShift($symbol);
                        ++$stack_pos;
                        $state_stack[$stack_pos] = $state = $action;
                        $this->sem_stack[$stack_pos] = $token_value;
                        $this->token_start_stack[$stack_pos] = $this->token_pos;
                        $this->token_end_stack[$stack_pos] = $this->token_pos;
                        $symbol = self::SYMBOL_NONE;
                        if ($this->error_state) {
                            --$this->error_state;
                        }
                        if ($action < $this->num_non_leaf_states) {
                            continue;
                        }
                        /* $yyn >= numNonLeafStates means shift-and-reduce */
                        $rule = $action - $this->num_non_leaf_states;
                    } else {
                        $rule = -$action;
                    }
                } else {
                    $rule = $this->action_default[$state];
                }
            }
            for (;;) {
                if ($rule === 0) {
                    /* accept */
                    //$this->traceAccept();
                    return $this->sem_value;
                }
                if ($rule !== $this->unexpected_token_rule) {
                    /* reduce */
                    //$this->traceReduce($rule);
                    $rule_length = $this->rule_to_length[$rule];
                    try {
                        $callback = $this->reduce_callbacks[$rule];
                        if ($callback !== null) {
                            $callback($this, $stack_pos);
                        } elseif ($rule_length > 0) {
                            $this->sem_value = $this->sem_stack[$stack_pos - $rule_length + 1];
                        }
                    } catch (Error $e) {
                        if (-1 === $e->get_start_line()) {
                            $e->set_start_line($this->tokens[$this->token_pos]->line);
                        }
                        $this->emit_error($e);
                        // Can't recover from this type of error
                        return null;
                    }
                    /* Goto - shift nonterminal */
                    $last_token_end = $this->token_end_stack[$stack_pos];
                    $stack_pos -= $rule_length;
                    $non_terminal = $this->rule_to_non_terminal[$rule];
                    $idx = $this->goto_base[$non_terminal] + $state_stack[$stack_pos];
                    if ($idx >= 0 && $idx < $this->goto_table_size && $this->goto_check[$idx] === $non_terminal) {
                        $state = $this->goto[$idx];
                    } else {
                        $state = $this->goto_default[$non_terminal];
                    }
                    ++$stack_pos;
                    $state_stack[$stack_pos] = $state;
                    $this->sem_stack[$stack_pos] = $this->sem_value;
                    $this->token_end_stack[$stack_pos] = $last_token_end;
                    if ($rule_length === 0) {
                        // Empty productions use the start attributes of the lookahead token.
                        $this->token_start_stack[$stack_pos] = $this->token_pos;
                    }
                } else {
                    /* error */
                    switch ($this->error_state) {
                        case 0:
                            $msg = $this->get_error_message($symbol, $state);
                            $this->emit_error(new Error($msg, $this->get_attributes_for_token($this->token_pos)));
                        // Break missing intentionally
                        // no break
                        case 1:
                        case 2:
                            $this->error_state = 3;
                            // Pop until error-expecting state uncovered
                            while (!(($idx = $this->action_base[$state] + $this->error_symbol) >= 0 && $idx < $this->action_table_size && $this->action_check[$idx] === $this->error_symbol || $state < $this->YY2TBLSTATE && ($idx = $this->action_base[$state + $this->num_non_leaf_states] + $this->error_symbol) >= 0 && $idx < $this->action_table_size && $this->action_check[$idx] === $this->error_symbol) || ($action = $this->action[$idx]) === $this->default_action) {
                                // Not totally sure about this
                                if ($stack_pos <= 0) {
                                    // Could not recover from error
                                    return null;
                                }
                                $state = $state_stack[--$stack_pos];
                                //$this->tracePop($state);
                            }
                            //$this->traceShift($this->errorSymbol);
                            ++$stack_pos;
                            $state_stack[$stack_pos] = $state = $action;
                            // We treat the error symbol as being empty, so we reset the end attributes
                            // to the end attributes of the last non-error symbol
                            $this->token_start_stack[$stack_pos] = $this->token_pos;
                            $this->token_end_stack[$stack_pos] = $this->token_end_stack[$stack_pos - 1];
                            break;
                        case 3:
                            if ($symbol === 0) {
                                // Reached EOF without recovering from error
                                return null;
                            }
                            //$this->traceDiscard($symbol);
                            $symbol = self::SYMBOL_NONE;
                            break 2;
                    }
                }
                if ($state < $this->num_non_leaf_states) {
                    break;
                }
                /* >= numNonLeafStates means shift-and-reduce */
                $rule = $state - $this->num_non_leaf_states;
            }
        }
    }
    protected function emit_error(Error $error): void
    {
        $this->error_handler->handle_error($error);
    }
    /**
     * Format error message including expected tokens.
     *
     * @param int $symbol Unexpected symbol
     * @param int $state State at time of error
     *
     * @return string Formatted error message
     */
    protected function get_error_message(int $symbol, int $state): string
    {
        $expected_string = '';
        if ($expected = $this->get_expected_tokens($state)) {
            $expected_string = ', expecting ' . implode(' or ', $expected);
        }
        return 'Syntax error, unexpected ' . $this->symbol_to_name[$symbol] . $expected_string;
    }
    /**
     * Get limited number of expected tokens in given state.
     *
     * @param int $state State
     *
     * @return string[] Expected tokens. If too many, an empty array is returned.
     */
    protected function get_expected_tokens(int $state): array
    {
        $expected = [];
        $base = $this->action_base[$state];
        foreach ($this->symbol_to_name as $symbol => $name) {
            $idx = $base + $symbol;
            if ($idx >= 0 && $idx < $this->action_table_size && $this->action_check[$idx] === $symbol || $state < $this->YY2TBLSTATE && ($idx = $this->action_base[$state + $this->num_non_leaf_states] + $symbol) >= 0 && $idx < $this->action_table_size && $this->action_check[$idx] === $symbol) {
                if ($this->action[$idx] !== $this->unexpected_token_rule && $this->action[$idx] !== $this->default_action && $symbol !== $this->error_symbol) {
                    if (count($expected) === 4) {
                        /* Too many expected tokens */
                        return [];
                    }
                    $expected[] = $name;
                }
            }
        }
        return $expected;
    }
    /**
     * Get attributes for a node with the given start and end token positions.
     *
     * @param int $tokenStartPos Token position the node starts at
     * @param int $tokenEndPos Token position the node ends at
     * @return array<string, mixed> Attributes
     */
    protected function get_attributes(int $token_start_pos, int $token_end_pos): array
    {
        $start_token = $this->tokens[$token_start_pos];
        $after_end_token = $this->tokens[$token_end_pos + 1];
        return ['startLine' => $start_token->line, 'startTokenPos' => $token_start_pos, 'startFilePos' => $start_token->pos, 'endLine' => $after_end_token->line, 'endTokenPos' => $token_end_pos, 'endFilePos' => $after_end_token->pos - 1];
    }
    /**
     * Get attributes for a single token at the given token position.
     *
     * @return array<string, mixed> Attributes
     */
    protected function get_attributes_for_token(int $token_pos): array
    {
        if ($token_pos < \count($this->tokens) - 1) {
            return $this->get_attributes($token_pos, $token_pos);
        }
        // Get attributes for the sentinel token.
        $token = $this->tokens[$token_pos];
        return ['startLine' => $token->line, 'startTokenPos' => $token_pos, 'startFilePos' => $token->pos, 'endLine' => $token->line, 'endTokenPos' => $token_pos, 'endFilePos' => $token->pos];
    }
    /*
     * Tracing functions used for debugging the parser.
     */
    /*
    protected function traceNewState($state, $symbol): void {
        echo '% State ' . $state
            . ', Lookahead ' . ($symbol == self::SYMBOL_NONE ? '--none--' : $this->symbolToName[$symbol]) . "\n";
    }
    
    protected function traceRead($symbol): void {
        echo '% Reading ' . $this->symbolToName[$symbol] . "\n";
    }
    
    protected function traceShift($symbol): void {
        echo '% Shift ' . $this->symbolToName[$symbol] . "\n";
    }
    
    protected function traceAccept(): void {
        echo "% Accepted.\n";
    }
    
    protected function traceReduce($n): void {
        echo '% Reduce by (' . $n . ') ' . $this->productions[$n] . "\n";
    }
    
    protected function tracePop($state): void {
        echo '% Recovering, uncovered state ' . $state . "\n";
    }
    
    protected function traceDiscard($symbol): void {
        echo '% Discard ' . $this->symbolToName[$symbol] . "\n";
    }
    */
    /*
     * Helper functions invoked by semantic actions
     */
    /**
     * Moves statements of semicolon-style namespaces into $ns->stmts and checks various error conditions.
     *
     * @param Node\Stmt[] $stmts
     * @return Node\Stmt[]
     */
    protected function handle_namespaces(array $stmts): array
    {
        $has_errored = false;
        $style = $this->get_namespacing_style($stmts);
        if (null === $style) {
            // not namespaced, nothing to do
            return $stmts;
        }
        if ('brace' === $style) {
            // For braced namespaces we only have to check that there are no invalid statements between the namespaces
            $after_first_namespace = false;
            foreach ($stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\Namespace_) {
                    $after_first_namespace = true;
                } elseif (!$stmt instanceof Node\Stmt\Halt_Compiler && !$stmt instanceof Node\Stmt\Nop && $after_first_namespace && !$has_errored) {
                    $this->emit_error(new Error('No code may exist outside of namespace {}', $stmt->get_attributes()));
                    $has_errored = true;
                    // Avoid one error for every statement
                }
            }
            return $stmts;
        }
        // For semicolon namespaces we have to move the statements after a namespace declaration into ->stmts
        $result_stmts = [];
        $target_stmts =& $result_stmts;
        $last_ns = null;
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Namespace_) {
                $this->fixup_namespace_attributes($last_ns);
                if ($stmt->stmts === null) {
                    $stmt->stmts = [];
                    $target_stmts =& $stmt->stmts;
                    $result_stmts[] = $stmt;
                } else {
                    // This handles the invalid case of mixed style namespaces
                    $result_stmts[] = $stmt;
                    $target_stmts =& $result_stmts;
                }
                $last_ns = $stmt;
            } elseif ($stmt instanceof Node\Stmt\Halt_Compiler) {
                // __halt_compiler() is not moved into the namespace
                $result_stmts[] = $stmt;
            } else {
                $target_stmts[] = $stmt;
            }
        }
        if ($last_ns !== null) {
            $this->fixup_namespace_attributes($last_ns);
        }
        return $result_stmts;
    }
    private function fixup_namespace_attributes(Node\Stmt\Namespace_ $stmt): void
    {
        // We moved the statements into the namespace node, as such the end of the namespace node
        // needs to be extended to the end of the statements.
        if (empty($stmt->stmts)) {
            return;
        }
        // We only move the builtin end attributes here. This is the best we can do with the
        // knowledge we have.
        $end_attributes = ['endLine', 'endFilePos', 'endTokenPos'];
        $last_stmt = $stmt->stmts[count($stmt->stmts) - 1];
        foreach ($end_attributes as $end_attribute) {
            if ($last_stmt->has_attribute($end_attribute)) {
                $stmt->set_attribute($end_attribute, $last_stmt->get_attribute($end_attribute));
            }
        }
    }
    /** @return array<string, mixed> */
    private function get_namespace_error_attributes(Namespace_ $node): array
    {
        $attrs = $node->get_attributes();
        // Adjust end attributes to only cover the "namespace" keyword, not the whole namespace.
        if (isset($attrs['startLine'])) {
            $attrs['endLine'] = $attrs['startLine'];
        }
        if (isset($attrs['startTokenPos'])) {
            $attrs['endTokenPos'] = $attrs['startTokenPos'];
        }
        if (isset($attrs['startFilePos'])) {
            $attrs['endFilePos'] = $attrs['startFilePos'] + \strlen('namespace') - 1;
        }
        return $attrs;
    }
    /**
     * Determine namespacing style (semicolon or brace)
     *
     * @param Node[] $stmts Top-level statements.
     *
     * @return null|string One of "semicolon", "brace" or null (no namespaces)
     */
    private function get_namespacing_style(array $stmts): ?string
    {
        $style = null;
        $has_not_allowed_stmts = false;
        foreach ($stmts as $i => $stmt) {
            if ($stmt instanceof Node\Stmt\Namespace_) {
                $current_style = null === $stmt->stmts ? 'semicolon' : 'brace';
                if (null === $style) {
                    $style = $current_style;
                    if ($has_not_allowed_stmts) {
                        $this->emit_error(new Error('Namespace declaration statement has to be the very first statement in the script', $this->get_namespace_error_attributes($stmt)));
                    }
                } elseif ($style !== $current_style) {
                    $this->emit_error(new Error('Cannot mix bracketed namespace declarations with unbracketed namespace declarations', $this->get_namespace_error_attributes($stmt)));
                    // Treat like semicolon style for namespace normalization
                    return 'semicolon';
                }
                continue;
            }
            /* declare(), __halt_compiler() and nops can be used before a namespace declaration */
            if ($stmt instanceof Node\Stmt\Declare_) {
                continue;
            }
            if ($stmt instanceof Node\Stmt\Halt_Compiler) {
                continue;
            }
            if ($stmt instanceof Node\Stmt\Nop) {
                continue;
            }
            /* There may be a hashbang line at the very start of the file */
            if ($i === 0 && $stmt instanceof Node\Stmt\Inline_Html && preg_match('/\A#!.*\r?\n\z/', $stmt->value)) {
                continue;
            }
            /* Everything else if forbidden before namespace declarations */
            $has_not_allowed_stmts = true;
        }
        return $style;
    }
    /** @return Name|Identifier */
    protected function handle_builtin_types(Name $name)
    {
        if (!$name->is_unqualified()) {
            return $name;
        }
        $lower_name = $name->to_lower_string();
        if (!$this->php_version->supports_builtin_type($lower_name)) {
            return $name;
        }
        return new Node\Identifier($lower_name, $name->get_attributes());
    }
    /**
     * Get combined start and end attributes at a stack location
     *
     * @param int $stackPos Stack location
     *
     * @return array<string, mixed> Combined start and end attributes
     */
    protected function get_attributes_at(int $stack_pos): array
    {
        return $this->get_attributes($this->token_start_stack[$stack_pos], $this->token_end_stack[$stack_pos]);
    }
    protected function get_float_cast_kind(string $cast): int
    {
        $cast = strtolower($cast);
        if (strpos($cast, 'float') !== false) {
            return Double::KIND_FLOAT;
        }
        if (strpos($cast, 'real') !== false) {
            return Double::KIND_REAL;
        }
        return Double::KIND_DOUBLE;
    }
    protected function get_int_cast_kind(string $cast): int
    {
        $cast = strtolower($cast);
        if (strpos($cast, 'integer') !== false) {
            return Expr\Cast\Int_::KIND_INTEGER;
        }
        return Expr\Cast\Int_::KIND_INT;
    }
    protected function get_bool_cast_kind(string $cast): int
    {
        $cast = strtolower($cast);
        if (strpos($cast, 'boolean') !== false) {
            return Expr\Cast\Bool_::KIND_BOOLEAN;
        }
        return Expr\Cast\Bool_::KIND_BOOL;
    }
    protected function get_string_cast_kind(string $cast): int
    {
        $cast = strtolower($cast);
        if (strpos($cast, 'binary') !== false) {
            return Expr\Cast\String_::KIND_BINARY;
        }
        return Expr\Cast\String_::KIND_STRING;
    }
    /** @param array<string, mixed> $attributes */
    protected function parse_l_number(string $str, array $attributes, bool $allow_invalid_octal = false): Int_
    {
        try {
            return Int_::from_string($str, $attributes, $allow_invalid_octal);
        } catch (Error $error) {
            $this->emit_error($error);
            // Use dummy value
            return new Int_(0, $attributes);
        }
    }
    /**
     * Parse a T_NUM_STRING token into either an integer or string node.
     *
     * @param string $str Number string
     * @param array<string, mixed> $attributes Attributes
     *
     * @return Int_|String_ Integer or string node.
     */
    protected function parse_num_string(string $str, array $attributes)
    {
        if (!preg_match('/^(?:0|-?[1-9][0-9]*)$/', $str)) {
            return new String_($str, $attributes);
        }
        $num = +$str;
        if (!is_int($num)) {
            return new String_($str, $attributes);
        }
        return new Int_($num, $attributes);
    }
    /** @param array<string, mixed> $attributes */
    protected function strip_indentation(string $string, int $indent_len, string $indent_char, bool $newline_at_start, bool $newline_at_end, array $attributes): string
    {
        if ($indent_len === 0) {
            return $string;
        }
        $start = $newline_at_start ? '(?:(?<=\n)|\A)' : '(?<=\n)';
        $end = $newline_at_end ? '(?:(?=[\r\n])|\z)' : '(?=[\r\n])';
        $regex = '/' . $start . '([ \t]*)(' . $end . ')?/';
        return preg_replace_callback($regex, function (array $matches) use ($indent_len, $indent_char, $attributes) {
            $prefix = substr($matches[1], 0, $indent_len);
            if (false !== strpos($prefix, $indent_char === ' ' ? "\t" : ' ')) {
                $this->emit_error(new Error('Invalid indentation - tabs and spaces cannot be mixed', $attributes));
            } elseif (strlen($prefix) < $indent_len && !isset($matches[2])) {
                $this->emit_error(new Error('Invalid body indentation level ' . '(expecting an indentation level of at least ' . $indent_len . ')', $attributes));
            }
            return substr($matches[0], strlen($prefix));
        }, $string);
    }
    /**
     * @param string|(Expr|InterpolatedStringPart)[] $contents
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $endTokenAttributes
     */
    protected function parse_doc_string(string $start_token, $contents, string $end_token, array $attributes, array $end_token_attributes, bool $parse_unicode_escape): Expr
    {
        $kind = strpos($start_token, "'") === false ? String_::KIND_HEREDOC : String_::KIND_NOWDOC;
        $regex = '/\A[bB]?<<<[ \t]*[\'"]?([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)[\'"]?(?:\r\n|\n|\r)\z/';
        $result = preg_match($regex, $start_token, $matches);
        assert($result === 1);
        $label = $matches[1];
        $result = preg_match('/\A[ \t]*/', $end_token, $matches);
        assert($result === 1);
        $indentation = $matches[0];
        $attributes['kind'] = $kind;
        $attributes['docLabel'] = $label;
        $attributes['docIndentation'] = $indentation;
        $indent_has_spaces = false !== strpos($indentation, ' ');
        $indent_has_tabs = false !== strpos($indentation, "\t");
        if ($indent_has_spaces && $indent_has_tabs) {
            $this->emit_error(new Error('Invalid indentation - tabs and spaces cannot be mixed', $end_token_attributes));
            // Proceed processing as if this doc string is not indented
            $indentation = '';
        }
        $indent_len = \strlen($indentation);
        $indent_char = $indent_has_spaces ? ' ' : "\t";
        if (\is_string($contents)) {
            if ($contents === '') {
                $attributes['rawValue'] = $contents;
                return new String_('', $attributes);
            }
            $contents = $this->strip_indentation($contents, $indent_len, $indent_char, true, true, $attributes);
            $contents = preg_replace('~(\r\n|\n|\r)\z~', '', $contents);
            $attributes['rawValue'] = $contents;
            if ($kind === String_::KIND_HEREDOC) {
                $contents = String_::parse_escape_sequences($contents, null, $parse_unicode_escape);
            }
            return new String_($contents, $attributes);
        }
        assert(count($contents) > 0);
        if (!$contents[0] instanceof Node\Interpolated_String_Part) {
            // If there is no leading encapsed string part, pretend there is an empty one
            $this->strip_indentation('', $indent_len, $indent_char, true, false, $contents[0]->get_attributes());
        }
        $new_contents = [];
        foreach ($contents as $i => $part) {
            if ($part instanceof Node\Interpolated_String_Part) {
                $is_last = $i === \count($contents) - 1;
                $part->value = $this->strip_indentation($part->value, $indent_len, $indent_char, $i === 0, $is_last, $part->get_attributes());
                if ($is_last) {
                    $part->value = preg_replace('~(\r\n|\n|\r)\z~', '', $part->value);
                }
                $part->set_attribute('rawValue', $part->value);
                $part->value = String_::parse_escape_sequences($part->value, null, $parse_unicode_escape);
                if ('' === $part->value) {
                    continue;
                }
            }
            $new_contents[] = $part;
        }
        return new Interpolated_String($new_contents, $attributes);
    }
    protected function create_comment_from_token(Token $token, int $token_pos): Comment
    {
        assert($token->id === \T_COMMENT || $token->id == \T_DOC_COMMENT);
        return \T_DOC_COMMENT === $token->id ? new Comment\Doc($token->text, $token->line, $token->pos, $token_pos, $token->get_end_line(), $token->get_end_pos() - 1, $token_pos) : new Comment($token->text, $token->line, $token->pos, $token_pos, $token->get_end_line(), $token->get_end_pos() - 1, $token_pos);
    }
    /**
     * Get last comment before the given token position, if any
     */
    protected function get_comment_before_token(int $token_pos): ?Comment
    {
        while (--$token_pos >= 0) {
            $token = $this->tokens[$token_pos];
            if (!isset($this->drop_tokens[$token->id])) {
                break;
            }
            if ($token->id === \T_COMMENT || $token->id === \T_DOC_COMMENT) {
                return $this->create_comment_from_token($token, $token_pos);
            }
        }
        return null;
    }
    /**
     * Create a zero-length nop to capture preceding comments, if any.
     */
    protected function maybe_create_zero_length_nop(int $token_pos): ?Nop
    {
        $comment = $this->get_comment_before_token($token_pos);
        if ($comment === null) {
            return null;
        }
        $comment_end_line = $comment->get_end_line();
        $comment_end_file_pos = $comment->get_end_file_pos();
        $comment_end_token_pos = $comment->get_end_token_pos();
        $attributes = ['startLine' => $comment_end_line, 'endLine' => $comment_end_line, 'startFilePos' => $comment_end_file_pos + 1, 'endFilePos' => $comment_end_file_pos, 'startTokenPos' => $comment_end_token_pos + 1, 'endTokenPos' => $comment_end_token_pos];
        return new Nop($attributes);
    }
    protected function maybe_create_nop(int $token_start_pos, int $token_end_pos): ?Nop
    {
        if ($this->get_comment_before_token($token_start_pos) === null) {
            return null;
        }
        return new Nop($this->get_attributes($token_start_pos, $token_end_pos));
    }
    protected function handle_halt_compiler(): string
    {
        // Prevent the lexer from returning any further tokens.
        $next_token = $this->tokens[$this->token_pos + 1];
        $this->token_pos = \count($this->tokens) - 2;
        // Return text after __halt_compiler.
        return $next_token->id === \T_INLINE_HTML ? $next_token->text : '';
    }
    protected function inline_html_has_leading_newline(int $stack_pos): bool
    {
        $token_pos = $this->token_start_stack[$stack_pos];
        $token = $this->tokens[$token_pos];
        assert($token->id == \T_INLINE_HTML);
        if ($token_pos > 0) {
            $prev_token = $this->tokens[$token_pos - 1];
            assert($prev_token->id == \T_CLOSE_TAG);
            return false !== strpos($prev_token->text, "\n") || false !== strpos($prev_token->text, "\r");
        }
        return true;
    }
    /**
     * @return array<string, mixed>
     */
    protected function create_empty_elem_attributes(int $token_pos): array
    {
        return $this->get_attributes_for_token($token_pos);
    }
    protected function fixup_array_destructuring(Array_ $node): Expr\List_
    {
        $this->created_arrays->offsetUnset($node);
        return new Expr\List_(array_map(function (Node\Array_Item $item): ?\Php_Parser\Node\Array_Item {
            if ($item->value instanceof Expr\Error) {
                // We used Error as a placeholder for empty elements, which are legal for destructuring.
                return null;
            }
            if ($item->value instanceof Array_) {
                return new Node\Array_Item($this->fixup_array_destructuring($item->value), $item->key, $item->by_ref, $item->get_attributes());
            }
            return $item;
        }, $node->items), ['kind' => Expr\List_::KIND_ARRAY] + $node->get_attributes());
    }
    protected function postprocess_list(Expr\List_ $node): void
    {
        foreach ($node->items as $i => $item) {
            if ($item->value instanceof Expr\Error) {
                // We used Error as a placeholder for empty elements, which are legal for destructuring.
                $node->items[$i] = null;
            }
        }
    }
    /** @param ElseIf_|Else_ $node */
    protected function fixup_alternative_else($node): void
    {
        // Make sure a trailing nop statement carrying comments is part of the node.
        $num_stmts = \count($node->stmts);
        if ($num_stmts !== 0 && $node->stmts[$num_stmts - 1] instanceof Nop) {
            $nop_attrs = $node->stmts[$num_stmts - 1]->get_attributes();
            if (isset($nop_attrs['endLine'])) {
                $node->set_attribute('endLine', $nop_attrs['endLine']);
            }
            if (isset($nop_attrs['endFilePos'])) {
                $node->set_attribute('endFilePos', $nop_attrs['endFilePos']);
            }
            if (isset($nop_attrs['endTokenPos'])) {
                $node->set_attribute('endTokenPos', $nop_attrs['endTokenPos']);
            }
        }
    }
    protected function check_class_modifier(int $a, int $b, int $modifier_pos): void
    {
        try {
            Modifiers::verify_class_modifier($a, $b);
        } catch (Error $error) {
            $error->set_attributes($this->get_attributes_at($modifier_pos));
            $this->emit_error($error);
        }
    }
    protected function check_modifier(int $a, int $b, int $modifier_pos): void
    {
        // Jumping through some hoops here because verifyModifier() is also used elsewhere
        try {
            Modifiers::verify_modifier($a, $b);
        } catch (Error $error) {
            $error->set_attributes($this->get_attributes_at($modifier_pos));
            $this->emit_error($error);
        }
    }
    protected function check_param(Param $node): void
    {
        if ($node->variadic && null !== $node->default) {
            $this->emit_error(new Error('Variadic parameter cannot have a default value', $node->default->get_attributes()));
        }
        if ($node->type instanceof Identifier && $node->type->name === 'void') {
            $this->emit_error(new Error('void cannot be used as a parameter type', $node->type->get_attributes()));
        }
    }
    protected function check_try_catch(Try_Catch $node): void
    {
        if (empty($node->catches) && null === $node->finally) {
            $this->emit_error(new Error('Cannot use try without catch or finally', $node->get_attributes()));
        }
    }
    protected function check_namespace(Namespace_ $node): void
    {
        if (null !== $node->stmts) {
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Namespace_) {
                    $this->emit_error(new Error('Namespace declarations cannot be nested', $stmt->get_attributes()));
                }
            }
        }
    }
    private function check_class_name(?Identifier $name, int $name_pos): void
    {
        if (null !== $name && $name->is_special_class_name()) {
            $this->emit_error(new Error(sprintf('Cannot use \'%s\' as class name as it is reserved', $name), $this->get_attributes_at($name_pos)));
        }
    }
    /** @param Name[] $interfaces */
    private function check_implemented_interfaces(array $interfaces): void
    {
        foreach ($interfaces as $interface) {
            if ($interface->is_special_class_name()) {
                $this->emit_error(new Error(sprintf('Cannot use \'%s\' as interface name as it is reserved', $interface), $interface->get_attributes()));
            }
        }
    }
    protected function check_class(Class_ $node, int $name_pos): void
    {
        $this->check_class_name($node->name, $name_pos);
        if ($node->extends && $node->extends->is_special_class_name()) {
            $this->emit_error(new Error(sprintf('Cannot use \'%s\' as class name as it is reserved', $node->extends), $node->extends->get_attributes()));
        }
        $this->check_implemented_interfaces($node->implements);
    }
    protected function check_interface(Interface_ $node, int $name_pos): void
    {
        $this->check_class_name($node->name, $name_pos);
        $this->check_implemented_interfaces($node->extends);
    }
    protected function check_enum(Enum_ $node, int $name_pos): void
    {
        $this->check_class_name($node->name, $name_pos);
        $this->check_implemented_interfaces($node->implements);
    }
    protected function check_class_method(Class_Method $node, int $modifier_pos): void
    {
        if ($node->flags & Modifiers::STATIC) {
            switch ($node->name->to_lower_string()) {
                case '__construct':
                    $this->emit_error(new Error(sprintf('Constructor %s() cannot be static', $node->name), $this->get_attributes_at($modifier_pos)));
                    break;
                case '__destruct':
                    $this->emit_error(new Error(sprintf('Destructor %s() cannot be static', $node->name), $this->get_attributes_at($modifier_pos)));
                    break;
                case '__clone':
                    $this->emit_error(new Error(sprintf('Clone method %s() cannot be static', $node->name), $this->get_attributes_at($modifier_pos)));
                    break;
            }
        }
        if ($node->flags & Modifiers::READONLY) {
            $this->emit_error(new Error(sprintf('Method %s() cannot be readonly', $node->name), $this->get_attributes_at($modifier_pos)));
        }
    }
    protected function check_class_const(Class_Const $node, int $modifier_pos): void
    {
        foreach ([Modifiers::STATIC, Modifiers::ABSTRACT, Modifiers::READONLY] as $modifier) {
            if ($node->flags & $modifier) {
                $this->emit_error(new Error("Cannot use '" . Modifiers::to_string($modifier) . "' as constant modifier", $this->get_attributes_at($modifier_pos)));
            }
        }
    }
    protected function check_use_use(Use_Item $node, int $name_pos): void
    {
        if ($node->alias && $node->alias->is_special_class_name()) {
            $this->emit_error(new Error(sprintf('Cannot use %s as %s because \'%2$s\' is a special class name', $node->name, $node->alias), $this->get_attributes_at($name_pos)));
        }
    }
    protected function check_property_hooks_for_multi_property(Property $property, int $hook_pos): void
    {
        if (count($property->props) > 1) {
            $this->emit_error(new Error('Cannot use hooks when declaring multiple properties', $this->get_attributes_at($hook_pos)));
        }
    }
    /** @param PropertyHook[] $hooks */
    protected function check_empty_property_hook_list(array $hooks, int $hook_pos): void
    {
        if (empty($hooks)) {
            $this->emit_error(new Error('Property hook list cannot be empty', $this->get_attributes_at($hook_pos)));
        }
    }
    protected function check_property_hook(Property_Hook $hook, ?int $param_list_pos): void
    {
        $name = $hook->name->to_lower_string();
        if ($name !== 'get' && $name !== 'set') {
            $this->emit_error(new Error('Unknown hook "' . $hook->name . '", expected "get" or "set"', $hook->name->get_attributes()));
        }
        if ($name === 'get' && $param_list_pos !== null) {
            $this->emit_error(new Error('get hook must not have a parameter list', $this->get_attributes_at($param_list_pos)));
        }
    }
    protected function check_property_hook_modifiers(int $a, int $b, int $modifier_pos): void
    {
        try {
            Modifiers::verify_modifier($a, $b);
        } catch (Error $error) {
            $error->set_attributes($this->get_attributes_at($modifier_pos));
            $this->emit_error($error);
        }
        if ($b != Modifiers::FINAL) {
            $this->emit_error(new Error('Cannot use the ' . Modifiers::to_string($b) . ' modifier on a property hook', $this->get_attributes_at($modifier_pos)));
        }
    }
    protected function check_constant_attributes(Const_ $node): void
    {
        if ($node->attr_groups !== [] && count($node->consts) > 1) {
            $this->emit_error(new Error('Cannot use attributes on multiple constants at once', $node->get_attributes()));
        }
    }
    protected function check_pipe_operator_parentheses(Expr $node): void
    {
        if ($node instanceof Expr\Arrow_Function && !$this->parenthesized_arrow_functions->offsetExists($node)) {
            $this->emit_error(new Error('Arrow functions on the right hand side of |> must be parenthesized', $node->get_attributes()));
        }
    }
    /**
     * @param Property|Param $node
     */
    protected function add_property_name_to_hooks(Node $node): void
    {
        if ($node instanceof Property) {
            $name = $node->props[0]->name->to_string();
        } else {
            $name = $node->var->name;
        }
        foreach ($node->hooks as $hook) {
            $hook->set_attribute('propertyName', $name);
        }
    }
    /** @param array<Node\Arg|Node\VariadicPlaceholder> $args */
    private function is_simple_exit(array $args): bool
    {
        if (\count($args) === 0) {
            return true;
        }
        if (\count($args) === 1) {
            $arg = $args[0];
            return $arg instanceof Arg && $arg->name === null && $arg->by_ref === false && $arg->unpack === false;
        }
        return false;
    }
    /**
     * @param array<Node\Arg|Node\VariadicPlaceholder> $args
     * @param array<string, mixed> $attrs
     */
    protected function create_exit_expr(string $name, int $name_pos, array $args, array $attrs): Expr
    {
        if ($this->is_simple_exit($args)) {
            // Create Exit node for backwards compatibility.
            $attrs['kind'] = strtolower($name) === 'exit' ? Expr\Exit_::KIND_EXIT : Expr\Exit_::KIND_DIE;
            return new Expr\Exit_(\count($args) === 1 ? $args[0]->value : null, $attrs);
        }
        return new Expr\Func_Call(new Name($name, $this->get_attributes_at($name_pos)), $args, $attrs);
    }
    /**
     * Creates the token map.
     *
     * The token map maps the PHP internal token identifiers
     * to the identifiers used by the Parser. Additionally it
     * maps T_OPEN_TAG_WITH_ECHO to T_ECHO and T_CLOSE_TAG to ';'.
     *
     * @return array<int, int> The token map
     */
    protected function create_token_map(): array
    {
        $token_map = [];
        // Single-char tokens use an identity mapping.
        for ($i = 0; $i < 256; ++$i) {
            $token_map[$i] = $i;
        }
        foreach ($this->symbol_to_name as $name) {
            if ($name[0] === 'T') {
                $token_map[\constant($name)] = constant(static::class . '::' . $name);
            }
        }
        // T_OPEN_TAG_WITH_ECHO with dropped T_OPEN_TAG results in T_ECHO
        $token_map[\T_OPEN_TAG_WITH_ECHO] = static::T_ECHO;
        // T_CLOSE_TAG is equivalent to ';'
        $token_map[\T_CLOSE_TAG] = ord(';');
        // We have created a map from PHP token IDs to external symbol IDs.
        // Now map them to the internal symbol ID.
        $full_token_map = [];
        foreach ($token_map as $php_token => $ext_symbol) {
            $int_symbol = $this->token_to_symbol[$ext_symbol];
            if ($int_symbol === $this->invalid_symbol) {
                continue;
            }
            $full_token_map[$php_token] = $int_symbol;
        }
        return $full_token_map;
    }
}