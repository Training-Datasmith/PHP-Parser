# PHP-Parser Architecture

## Purpose

PHP-Parser is a library that parses PHP source code into an Abstract Syntax Tree (AST). It is widely used by static analysis tools, code refactoring tools, and code generators to programmatically inspect or transform PHP code.

## Directory Structure

```
lib/PhpParser/
├── Builder/             — Fluent AST node builder helpers (build classes, methods, params, etc.)
├── Comment/             — Comment and DocComment value objects attached to nodes
├── ErrorHandler/        — Strategies for handling parse errors (Throwing vs. Collecting)
├── Internal/            — Internal implementation details, not part of public API
├── Lexer/               — Tokeniser (wraps PHP's native token_get_all)
├── Node/                — All AST node classes, organised by category:
│   ├── Expr/            — Expression nodes (binary ops, calls, closures, etc.)
│   ├── Name/            — Fully-qualified, relative, and reserved name nodes
│   ├── Scalar/          — Scalar literal nodes (int, float, string, magic consts)
│   └── Stmt/            — Statement nodes (class, function, if, foreach, etc.)
├── NodeVisitor/         — Built-in visitor implementations (NameResolver, FindingVisitor, etc.)
├── Parser/              — Concrete parser implementations (Php7, Php8)
├── Builder.php          — Entry point for the fluent builder API
├── ConstExprEvaluator.php — Evaluates constant PHP expressions to PHP values
├── Error.php            — Parse error with source location
├── JsonDecoder.php      — Reconstructs AST nodes from JSON
├── Lexer.php            — Tokenises PHP source and produces Token objects
├── Modifiers.php        — Bitmask constants for visibility / abstract / readonly
├── NodeAbstract.php     — Base class for all AST nodes
├── NodeDumper.php       — Renders an AST as a human-readable string
├── NodeFinder.php       — Searches an AST for nodes matching a predicate
├── NodeTraverser.php    — Walks an AST depth-first, calling registered visitors
├── NodeVisitorAbstract.php — No-op base implementation of NodeVisitor
├── Parser.php           — Parser interface
├── ParserAbstract.php   — LALR(1) parser engine shared by all concrete parsers
├── PhpVersion.php       — Encapsulates the target PHP version for parsing
└── PrettyPrinter/       — Converts an AST back into PHP source code
```

## Key Design Decisions

- **Visitor pattern for tree walking** — `NodeTraverser` applies a list of `NodeVisitor` implementations in sequence. Visitors can replace, remove, or halt traversal using sentinel return values (`REMOVE_NODE`, `STOP_TRAVERSAL`, etc.).
- **Mutable nodes** — AST nodes are plain mutable objects with public properties. This simplifies in-place transformations but requires care to avoid aliasing issues.
- **Error recovery** — The parser can continue past errors when given a `Collecting` error handler, returning a partial AST and accumulating errors for later inspection.
- **Attribute bag** — Every node carries an `attributes` array storing source positions and comments. This separates structural AST data from metadata without polluting the node class hierarchy.
- **Pretty-printing round-trips** — `PrettyPrinterAbstract` can regenerate PHP source from the AST. The `Standard` subclass preserves original formatting where possible using original token positions.

## Extension Points

- **Custom visitors** — Implement `NodeVisitor` (or extend `NodeVisitorAbstract`) and add to a `NodeTraverser`.
- **Custom nodes** — Extend `NodeAbstract` and implement `getSubNodeNames()` and `getType()`.
- **Custom pretty-printing** — Extend `PrettyPrinter\Standard` and override the relevant `print*` methods.
- **Fallback evaluator** — Pass a callable to `ConstExprEvaluator` to handle node types the built-in evaluator cannot resolve (e.g. class constants).

## Dependency Flow

```
Consumer code
  └─► Parser (parse PHP source → Node[])
        ├─► Lexer (source → Token[])
        └─► ParserAbstract (tokens → AST via LALR tables)

Consumer code
  └─► NodeTraverser (walk AST)
        └─► NodeVisitor[] (inspect / transform nodes)

Consumer code
  └─► PrettyPrinter\Standard (Node[] → PHP source string)
```

## Versioning Notes

- Library version 5.x supports parsing PHP 5.x through PHP 8.4.
- Requires PHP ≥ 7.4 to run.
- The `PhpVersion` object controls which syntax features are recognised during parsing.
