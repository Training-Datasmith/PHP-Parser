<?php

declare(strict_types=1);

namespace Php_Parser;

use Php_Parser\Node\Expr;
use Php_Parser\Node\Interpolated_String_Part;
use Php_Parser\Node\Name;
use Php_Parser\Node\Scalar;
use Php_Parser\Node\Stmt;

class Compatibility_Test extends \PHPUnit\Framework\TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAliases1(): void
    {
        $var = new Expr\Variable('x');
        $node = new Node\Closure_Use($var);
        $this->assertTrue($node instanceof Expr\ClosureUse);
        $node = new Node\Array_Item($var);
        $this->assertTrue($node instanceof Expr\ArrayItem);
        $node = new Node\Static_Var($var);
        $this->assertTrue($node instanceof Stmt\StaticVar);
        $node = new Scalar\Float_(1.0);
        $this->assertTrue($node instanceof Scalar\DNumber);
        $node = new Scalar\Int_(1);
        $this->assertTrue($node instanceof Scalar\LNumber);
        $part = new Interpolated_String_Part('foo');
        $this->assertTrue($part instanceof Scalar\EncapsedStringPart);
        $node = new Scalar\Interpolated_String([$part]);
        $this->assertTrue($node instanceof Scalar\Encapsed);
        $node = new Node\Declare_Item('strict_types', new Scalar\Int_(1));
        $this->assertTrue($node instanceof Stmt\DeclareDeclare);
        $node = new Node\Property_Item('x');
        $this->assertTrue($node instanceof Stmt\PropertyProperty);
        $node = new Node\Use_Item(new Name('X'));
        $this->assertTrue($node instanceof Stmt\UseUse);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAliases2(): void
    {
        $var = new Expr\Variable('x');
        $node = new Node\Expr\ClosureUse($var);
        $this->assertTrue($node instanceof Node\Closure_Use);
        $node = new Node\Expr\ArrayItem($var);
        $this->assertTrue($node instanceof Node\Array_Item);
        $node = new Node\Stmt\StaticVar($var);
        $this->assertTrue($node instanceof Node\Static_Var);
        $node = new Node\Scalar\DNumber(1.0);
        $this->assertTrue($node instanceof Scalar\Float_);
        $node = new Node\Scalar\LNumber(1);
        $this->assertTrue($node instanceof Scalar\Int_);
        $part = new Node\Scalar\EncapsedStringPart('foo');
        $this->assertTrue($part instanceof Node\Interpolated_String_Part);
        $node = new Scalar\Encapsed([$part]);
        $this->assertTrue($node instanceof Scalar\Interpolated_String);
        $node = new Stmt\DeclareDeclare('strict_types', new Scalar\LNumber(1));
        $this->assertTrue($node instanceof Node\Declare_Item);
        $node = new Stmt\PropertyProperty('x');
        $this->assertTrue($node instanceof Node\Property_Item);
        $node = new Stmt\UseUse(new Name('X'));
        $this->assertTrue($node instanceof Node\Use_Item);
    }
}
