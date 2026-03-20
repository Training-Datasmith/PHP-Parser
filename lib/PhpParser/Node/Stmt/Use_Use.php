<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node\Use_Item;
require __DIR__ . '/../UseItem.php';
if (false) {
    /**
     * For classmap-authoritative support.
     *
     * @deprecated use \PhpParser\Node\UseItem instead.
     */
    class Use_Use extends Use_Item
    {
    }
}