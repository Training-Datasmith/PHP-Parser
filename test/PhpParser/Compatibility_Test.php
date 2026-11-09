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
    public function testRenamedNodeClassesInstantiate(): void
    {
        $var = new Expr\Variable('x');
        $this->assertInstanceOf(Node\Closure_Use::class, new Node\Closure_Use($var));
        $this->assertInstanceOf(Node\Array_Item::class, new Node\Array_Item($var));
        $this->assertInstanceOf(Node\Static_Var::class, new Node\Static_Var($var));
        $this->assertInstanceOf(Scalar\Float_::class, new Scalar\Float_(1.0));
        $this->assertInstanceOf(Scalar\Int_::class, new Scalar\Int_(1));
        $part = new Interpolated_String_Part('foo');
        $this->assertInstanceOf(Interpolated_String_Part::class, $part);
        $this->assertInstanceOf(Scalar\Interpolated_String::class, new Scalar\Interpolated_String([$part]));
        $this->assertInstanceOf(Node\Declare_Item::class, new Node\Declare_Item('strict_types', new Scalar\Int_(1)));
        $this->assertInstanceOf(Node\Property_Item::class, new Node\Property_Item('x'));
        $this->assertInstanceOf(Node\Use_Item::class, new Node\Use_Item(new Name('X')));
    }

    public function testRenamedNodeClassesFromLegacyPaths(): void
    {
        $var = new Expr\Variable('x');
        $this->assertInstanceOf(Node\Closure_Use::class, new Node\Closure_Use($var));
        $this->assertInstanceOf(Node\Array_Item::class, new Node\Array_Item($var));
        $this->assertInstanceOf(Node\Static_Var::class, new Node\Static_Var($var));
        $this->assertInstanceOf(Scalar\Float_::class, new Scalar\Float_(1.0));
        $this->assertInstanceOf(Scalar\Int_::class, new Scalar\Int_(1));
        $part = new Interpolated_String_Part('foo');
        $this->assertInstanceOf(Interpolated_String_Part::class, $part);
        $this->assertInstanceOf(Scalar\Interpolated_String::class, new Scalar\Interpolated_String([$part]));
        $this->assertInstanceOf(Node\Declare_Item::class, new Node\Declare_Item('strict_types', new Scalar\Int_(1)));
        $this->assertInstanceOf(Node\Property_Item::class, new Node\Property_Item('x'));
        $this->assertInstanceOf(Node\Use_Item::class, new Node\Use_Item(new Name('X')));
    }

}
