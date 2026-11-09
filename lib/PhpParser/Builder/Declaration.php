<?php

declare (strict_types=1);
namespace Php_Parser\Builder;

use Php_Parser;
use Php_Parser\Builder_Helpers;
abstract class Declaration implements Php_Parser\Builder
{
    /** @var array<string, mixed> */
    protected array $attributes = [];
    /**
     * Adds a statement.
     *
     * @param PhpParser\Node\Stmt|PhpParser\Builder $stmt The statement to add
     *
     * @return $this The builder instance (for fluid interface)
     */
    abstract public function add_stmt($stmt);
    /**
     * Adds multiple statements.
     *
     * @param (PhpParser\Node\Stmt|PhpParser\Builder)[] $stmts The statements to add
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function add_stmts(array $stmts)
    {
        foreach ($stmts as $stmt) {
            $this->add_stmt($stmt);
        }
        return $this;
    }
    /**
     * Sets doc comment for the declaration.
     *
     * @param PhpParser\Comment\Doc|string $docComment Doc comment to set
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function set_doc_comment($doc_comment)
    {
        $this->attributes['comments'] = [Builder_Helpers::normalize_doc_comment($doc_comment)];
        return $this;
    }
}