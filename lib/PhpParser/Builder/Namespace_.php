<?php

declare (strict_types=1);
namespace Php_Parser\Builder;

use Php_Parser;
use Php_Parser\Builder_Helpers;
use Php_Parser\Node;
use Php_Parser\Node\Stmt;
class Namespace_ extends Declaration
{
    private ?Node\Name $name;
    /** @var Stmt[] */
    private array $stmts = [];
    /**
     * Creates a namespace builder.
     *
     * @param Node\Name|string|null $name Name of the namespace
     */
    public function __construct($name)
    {
        $this->name = null !== $name ? Builder_Helpers::normalize_name($name) : null;
    }
    /**
     * Adds a statement.
     *
     * @param Node|PhpParser\Builder $stmt The statement to add
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function add_stmt($stmt)
    {
        $this->stmts[] = Builder_Helpers::normalize_stmt($stmt);
        return $this;
    }
    /**
     * Returns the built node.
     *
     * @return Stmt\Namespace_ The built node
     */
    public function get_node(): Node
    {
        return new Stmt\Namespace_($this->name, $this->stmts, $this->attributes);
    }
}