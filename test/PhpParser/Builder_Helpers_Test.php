<?php

declare(strict_types=1);

namespace Php_Parser;

use Php_Parser\Builder\Class_;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Parser\Node\Scalar;
use Php_Parser\Node\Stmt;

class Builder_Helpers_Test extends \PHPUnit\Framework\TestCase
{
    public function testNormalizeNode(): void
    {
        $builder = new Class_('SomeClass');
        $this->assertEquals($builder->get_node(), Builder_Helpers::normalize_node($builder));

        $attribute = new Node\Attribute(new Node\Name('Test'));
        $this->assertSame($attribute, Builder_Helpers::normalize_node($attribute));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected node or builder object');
        Builder_Helpers::normalize_node('test');
    }

    public function testNormalizeStmt(): void
    {
        $stmt = new Node\Stmt\Class_('Class');
        $this->assertSame($stmt, Builder_Helpers::normalize_stmt($stmt));

        $expr = new Expr\Variable('fn');
        $normalizedExpr = Builder_Helpers::normalize_stmt($expr);
        $this->assertEquals(new Stmt\Expression($expr), $normalizedExpr);
        $this->assertSame($expr, $normalizedExpr->expr);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected statement or expression node');
        Builder_Helpers::normalize_stmt(new Node\Attribute(new Node\Name('Test')));
    }

    public function testNormalizeStmtInvalidType(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected node or builder object');
        Builder_Helpers::normalize_stmt('test');
    }

    public function testNormalizeIdentifier(): void
    {
        $identifier = new Node\Identifier('fn');
        $this->assertSame($identifier, Builder_Helpers::normalize_identifier($identifier));
        $this->assertEquals($identifier, Builder_Helpers::normalize_identifier('fn'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected string or instance of Node\Identifier');
        Builder_Helpers::normalize_identifier(1);
    }

    public function testNormalizeIdentifierOrExpr(): void
    {
        $identifier = new Node\Identifier('fn');
        $this->assertSame($identifier, Builder_Helpers::normalize_identifier_or_expr($identifier));

        $expr = new Expr\Variable('fn');
        $this->assertSame($expr, Builder_Helpers::normalize_identifier_or_expr($expr));
        $this->assertEquals($identifier, Builder_Helpers::normalize_identifier_or_expr('fn'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected string or instance of Node\Identifier');
        Builder_Helpers::normalize_identifier_or_expr(1);
    }

    public function testNormalizeName(): void
    {
        $name = new Node\Name('test');
        $this->assertSame($name, Builder_Helpers::normalize_name($name));
        $this->assertEquals(
            new Node\Name\Fully_Qualified(['Namespace', 'Test']),
            Builder_Helpers::normalize_name('\\Namespace\\Test')
        );
        $this->assertEquals(
            new Node\Name\Relative(['Test']),
            Builder_Helpers::normalize_name('namespace\\Test')
        );
        $this->assertEquals($name, Builder_Helpers::normalize_name('test'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Name cannot be empty');
        Builder_Helpers::normalize_name('');
    }

    public function testNormalizeNameInvalidType(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Name must be a string or an instance of Node\Name');
        Builder_Helpers::normalize_name(1);
    }

    public function testNormalizeNameOrExpr(): void
    {
        $expr = new Expr\Variable('fn');
        $this->assertSame($expr, Builder_Helpers::normalize_name_or_expr($expr));

        $name = new Node\Name('test');
        $this->assertSame($name, Builder_Helpers::normalize_name_or_expr($name));
        $this->assertEquals(
            new Node\Name\Fully_Qualified(['Namespace', 'Test']),
            Builder_Helpers::normalize_name_or_expr('\\Namespace\\Test')
        );
        $this->assertEquals(
            new Node\Name\Relative(['Test']),
            Builder_Helpers::normalize_name_or_expr('namespace\\Test')
        );
        $this->assertEquals($name, Builder_Helpers::normalize_name_or_expr('test'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Name cannot be empty');
        Builder_Helpers::normalize_name_or_expr('');
    }

    public function testNormalizeNameOrExpInvalidType(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Name must be a string or an instance of Node\Name or Node\Expr');
        Builder_Helpers::normalize_name_or_expr(1);
    }

    public function testNormalizeType(): void
    {
        $this->assertEquals(new Node\Identifier('array'), Builder_Helpers::normalize_type('array'));
        $this->assertEquals(new Node\Identifier('callable'), Builder_Helpers::normalize_type('callable'));
        $this->assertEquals(new Node\Identifier('string'), Builder_Helpers::normalize_type('string'));
        $this->assertEquals(new Node\Identifier('int'), Builder_Helpers::normalize_type('int'));
        $this->assertEquals(new Node\Identifier('float'), Builder_Helpers::normalize_type('float'));
        $this->assertEquals(new Node\Identifier('bool'), Builder_Helpers::normalize_type('bool'));
        $this->assertEquals(new Node\Identifier('iterable'), Builder_Helpers::normalize_type('iterable'));
        $this->assertEquals(new Node\Identifier('void'), Builder_Helpers::normalize_type('void'));
        $this->assertEquals(new Node\Identifier('object'), Builder_Helpers::normalize_type('object'));
        $this->assertEquals(new Node\Identifier('null'), Builder_Helpers::normalize_type('null'));
        $this->assertEquals(new Node\Identifier('false'), Builder_Helpers::normalize_type('false'));
        $this->assertEquals(new Node\Identifier('mixed'), Builder_Helpers::normalize_type('mixed'));
        $this->assertEquals(new Node\Identifier('never'), Builder_Helpers::normalize_type('never'));
        $this->assertEquals(new Node\Identifier('true'), Builder_Helpers::normalize_type('true'));

        $intIdentifier = new Node\Identifier('int');
        $this->assertSame($intIdentifier, Builder_Helpers::normalize_type($intIdentifier));

        $intName = new Node\Name('int');
        $this->assertSame($intName, Builder_Helpers::normalize_type($intName));

        $intNullable = new Node\Nullable_Type(new Identifier('int'));
        $this->assertSame($intNullable, Builder_Helpers::normalize_type($intNullable));

        $unionType = new Node\Union_Type([new Node\Identifier('int'), new Node\Identifier('string')]);
        $this->assertSame($unionType, Builder_Helpers::normalize_type($unionType));

        $intersectionType = new Node\Intersection_Type([new Node\Name('A'), new Node\Name('B')]);
        $this->assertSame($intersectionType, Builder_Helpers::normalize_type($intersectionType));

        $expectedNullable = new Node\Nullable_Type($intIdentifier);
        $nullable = Builder_Helpers::normalize_type('?int');
        $this->assertEquals($expectedNullable, $nullable);
        $this->assertEquals($intIdentifier, $nullable->type);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Type must be a string, or an instance of Name, Identifier or ComplexType');
        Builder_Helpers::normalize_type(1);
    }

    public function testNormalizeTypeNullableVoid(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('void type cannot be nullable');
        Builder_Helpers::normalize_type('?void');
    }

    public function testNormalizeTypeNullableMixed(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('mixed type cannot be nullable');
        Builder_Helpers::normalize_type('?mixed');
    }

    public function testNormalizeTypeNullableNever(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('never type cannot be nullable');
        Builder_Helpers::normalize_type('?never');
    }

    public function testNormalizeValue(): void
    {
        $expression = new Scalar\Int_(1);
        $this->assertSame($expression, Builder_Helpers::normalize_value($expression));

        $this->assertEquals(new Expr\Const_Fetch(new Node\Name('null')), Builder_Helpers::normalize_value(null));
        $this->assertEquals(new Expr\Const_Fetch(new Node\Name('true')), Builder_Helpers::normalize_value(true));
        $this->assertEquals(new Expr\Const_Fetch(new Node\Name('false')), Builder_Helpers::normalize_value(false));
        $this->assertEquals(new Scalar\Int_(2), Builder_Helpers::normalize_value(2));
        $this->assertEquals(new Scalar\Float_(2.5), Builder_Helpers::normalize_value(2.5));
        $this->assertEquals(new Scalar\String_('text'), Builder_Helpers::normalize_value('text'));
        $this->assertEquals(
            new Expr\Array_([
                new Node\Array_Item(new Scalar\Int_(0)),
                new Node\Array_Item(new Scalar\Int_(1), new Scalar\String_('test')),
            ]),
            Builder_Helpers::normalize_value([
                0,
                'test' => 1,
            ])
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid value');
        Builder_Helpers::normalize_value(new \stdClass());
    }

    public function testNormalizeDocComment(): void
    {
        $docComment = new Comment\Doc('Some doc comment');
        $this->assertSame($docComment, Builder_Helpers::normalize_doc_comment($docComment));

        $this->assertEquals($docComment, Builder_Helpers::normalize_doc_comment('Some doc comment'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Doc comment must be a string or an instance of Php_Parser\Comment\Doc');
        Builder_Helpers::normalize_doc_comment(1);
    }

    public function testNormalizeAttribute(): void
    {
        $attribute = new Node\Attribute(new Node\Name('Test'));
        $attributeGroup = new Node\Attribute_Group([$attribute]);

        $this->assertEquals($attributeGroup, Builder_Helpers::normalize_attribute($attribute));
        $this->assertSame($attributeGroup, Builder_Helpers::normalize_attribute($attributeGroup));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Attribute must be an instance of Php_Parser\Node\Attribute or Php_Parser\Node\Attribute_Group');
        Builder_Helpers::normalize_attribute('test');
    }

    public function testNormalizeValueEnum()
    {
        if (\PHP_VERSION_ID < 80100) {
            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage('Invalid value');
            Builder_Helpers::normalize_value(new \stdClass());
            return;
        }

        include __DIR__ . '/../fixtures/Suit.php';

        $this->assertEquals(
            new Expr\Class_Const_Fetch(new Fully_Qualified(\Suit::class), new Identifier('Hearts')),
            Builder_Helpers::normalize_value(\Suit::Hearts)
        );
    }
}
