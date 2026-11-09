<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node;
abstract class Trait_Use_Adaptation extends Node\Stmt
{
    /** @var Node\Name|null Trait name */
    public ?Node\Name $trait = null;
    /** @var Node\Identifier Method name */
    public Node\Identifier $method;
}