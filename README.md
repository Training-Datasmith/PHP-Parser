PHP Parser
==========

[![Coverage Status](https://coveralls.io/repos/github/nikic/PHP-Parser/badge.svg?branch=master)](https://coveralls.io/github/nikic/PHP-Parser?branch=master)

This is a PHP parser written in PHP. Its purpose is to simplify static code analysis and
manipulation.

[**Documentation for version 5.x**][doc_master] (current; for running on PHP >= 7.4; for parsing PHP 7.0 to PHP 8.4, with limited support for parsing PHP 5.x).

[Documentation for version 4.x][doc_4_x] (supported; for running on PHP >= 7.0; for parsing PHP 5.2 to PHP 8.3).

Features
--------

The main features provided by this library are:

 * Parsing PHP 7, and PHP 8 code into an abstract syntax tree (AST).
   * Invalid code can be parsed into a partial AST.
   * The AST contains accurate location information.
 * Dumping the AST in human-readable form.
 * Converting an AST back to PHP code.
   * Formatting can be preserved for partially changed ASTs.
 * Infrastructure to traverse and modify ASTs.
 * Resolution of namespaced names.
 * Evaluation of constant expressions.
 * Builders to simplify AST construction for code generation.
 * Converting an AST into JSON and back.

Quick Start
-----------

Install the library using [composer](https://getcomposer.org):

    php composer.phar require nikic/php-parser

Parse some PHP code into an AST and dump the result in human-readable form:

```php
<?php
use Php_Parser\Error;
use Php_Parser\Node_Dumper;
use Php_Parser\Parser_Factory;

$code = <<<'CODE'
<?php

function test($foo)
{
    var_dump($foo);
}
CODE;

$parser = (new Parser_Factory())->create_for_newest_supported_version();
try {
    $ast = $parser->parse($code);
} catch (Error $error) {
    echo "Parse error: {$error->getMessage()}\n";
    return;
}

$dumper = new Node_Dumper;
echo $dumper->dump($ast) . "\n";
```

This dumps an AST looking something like this:

```
array(
    0: Stmt_Function(
        attr_groups: array(
        )
        by_ref: false
        name: Identifier(
            name: test
        )
        params: array(
            0: Param(
                attr_groups: array(
                )
                flags: 0
                type: null
                by_ref: false
                variadic: false
                var: Expr_Variable(
                    name: foo
                )
                default: null
            )
        )
        return_type: null
        stmts: array(
            0: Stmt_Expression(
                expr: Expr_FuncCall(
                    name: Name(
                        name: var_dump
                    )
                    args: array(
                        0: Arg(
                            name: null
                            value: Expr_Variable(
                                name: foo
                            )
                            by_ref: false
                            unpack: false
                        )
                    )
                )
            )
        )
    )
)
```

Let's traverse the AST and perform some kind of modification. For example, drop all function bodies:

```php
use Php_Parser\Node;
use Php_Parser\Node\Stmt\Function_;
use Php_Parser\Node_Traverser;
use Php_Parser\Node_Visitor_Abstract;

$traverser = new Node_Traverser();
$traverser->add_visitor(new class extends Node_Visitor_Abstract {
    public function enterNode(Node $node) {
        if ($node instanceof Function_) {
            // Clean out the function body
            $node->stmts = [];
        }
    }
});

$ast = $traverser->traverse($ast);
echo $dumper->dump($ast) . "\n";
```

This gives us an AST where the `Function_::$stmts` are empty:

```
array(
    0: Stmt_Function(
        attr_groups: array(
        )
        by_ref: false
        name: Identifier(
            name: test
        )
        params: array(
            0: Param(
                attr_groups: array(
                )
                type: null
                by_ref: false
                variadic: false
                var: Expr_Variable(
                    name: foo
                )
                default: null
            )
        )
        return_type: null
        stmts: array(
        )
    )
)
```

Finally, we can convert the new AST back to PHP code:

```php
use Php_Parser\Pretty_Printer;

$prettyPrinter = new Pretty_Printer\Standard;
echo $prettyPrinter->pretty_print_file($ast);
```

This gives us our original code, minus the `var_dump()` call inside the function:

```php
<?php

function test($foo)
{
}
```

For a more comprehensive introduction, see the documentation.

### Node type identifiers (`get_type()`)

Each AST node exposes a stable `get_type()` string derived from its renamed PHP class: take the class name under `Php_Parser\Node\`, join namespace segments with `_`, and keep reserved-word trailing underscores (for example `Php_Parser\Node\Stmt\Class_` → `Stmt_Class_`, `Php_Parser\Node\Expr\Binary_Op\Equal` → `Expr_Binary_Op_Equal`). The human-readable dumper, JSON `nodeType` values, and `Json_Decoder` all use this snake_case form exclusively.

Documentation
-------------

 1. [Introduction](doc/0_Introduction.markdown)
 2. [Usage of basic components](doc/2_Usage_of_basic_components.markdown)

Component documentation:

 * [Walking the AST](doc/component/Walking_the_AST.markdown)
   * Node visitors
   * Modifying the AST from a visitor
   * Short-circuiting traversals
   * Interleaved visitors
   * Simple node finding API
   * Parent and sibling references
 * [Name resolution](doc/component/Name_resolution.markdown)
   * Name resolver options
   * Name resolution context
 * [Pretty printing](doc/component/Pretty_printing.markdown)
   * Converting AST back to PHP code
   * Customizing formatting
   * Formatting-preserving code transformations
 * [AST builders](doc/component/AST_builders.markdown)
   * Fluent builders for AST nodes
 * [Lexer](doc/component/Lexer.markdown)
   * Emulation
   * Tokens, positions and attributes
 * [Error handling](doc/component/Error_handling.markdown)
   * Column information for errors
   * Error recovery (parsing of syntactically incorrect code)
 * [Constant expression evaluation](doc/component/Constant_expression_evaluation.markdown)
   * Evaluating constant/property/etc initializers
   * Handling errors and unsupported expressions
 * [JSON representation](doc/component/JSON_representation.markdown)
   * JSON encoding and decoding of ASTs
 * [Performance](doc/component/Performance.markdown)
   * Disabling Xdebug
   * Reusing objects
   * Garbage collection impact
 * [Frequently asked questions](doc/component/FAQ.markdown)
   * Parent and sibling references

 [doc_3_x]: https://github.com/nikic/PHP-Parser/tree/3.x/doc
 [doc_4_x]: https://github.com/nikic/PHP-Parser/tree/4.x/doc
 [doc_master]: https://github.com/nikic/PHP-Parser/tree/master/doc
