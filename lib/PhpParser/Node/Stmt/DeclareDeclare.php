<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node\Declare_Item;
require __DIR__ . '/../DeclareItem.php';
if (false) {
    /**
     * For classmap-authoritative support.
     *
     * @deprecated use \PhpParser\Node\DeclareItem instead.
     */
    class Declare_Declare extends Declare_Item
    {
    }
}