Error handling
==============

Errors during parsing or analysis are represented using the `Php_Parser\Error` exception class. In addition to an error
message, an error can also store additional information about the location the error occurred at.

How much location information is available depends on the origin of the error. At a minimum the start line of the error
is usually available.

Column information
------------------

Before using column information, its availability needs to be checked with `$e->hasColumnInfo()`, as the precise
location of an error cannot always be determined. The methods for retrieving column information also have to be passed
the source code of the parsed file. An example for printing an error:

```php
if ($e->hasColumnInfo()) {
    echo $e->getRawMessage() . ' from ' . $e->get_start_line() . ':' . $e->getStartColumn($code)
        . ' to ' . $e->getEndLine() . ':' . $e->getEndColumn($code);
    // or:
    echo $e->getMessageWithColumnInfo($code);
} else {
    echo $e->getMessage();
}
```

Both line numbers and column numbers are 1-based. EOF errors will be located at the position one past the end of the
file.

Error recovery
--------------

The error behavior of the parser (and other components) is controlled by an `Error_Handler`. Whenever an error is
encountered, `Error_Handler::handleError()` is invoked. The default error handling strategy is `Error_Handler\Throwing`,
which will immediately throw when an error is encountered.

To instead collect all encountered errors into an array, while trying to continue parsing the rest of the source code,
an instance of `Error_Handler\Collecting` can be passed to the `Parser::parse()` method. A usage example:

```php
$parser = (new Php_Parser\Parser_Factory())->create_for_host_version();
$errorHandler = new Php_Parser\Error_Handler\Collecting;

$stmts = $parser->parse($code, $errorHandler);

if ($errorHandler->hasErrors()) {
    foreach ($errorHandler->getErrors() as $error) {
        // $error is an ordinary Php_Parser\Error
    }
}

if (null !== $stmts) {
    // $stmts is a best-effort partial AST
}
```

The partial AST may contain `Expr\Error` nodes that indicate that an error occurred while parsing an expression.

The `NameResolver` visitor also accepts an `Error_Handler` as a constructor argument.
