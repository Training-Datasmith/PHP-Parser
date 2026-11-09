<?php

declare(strict_types=1);

namespace Php_Parser\Builder;

use Php_Parser\Comment;
use Php_Parser\Node;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Attribute;
use Php_Parser\Node\Attribute_Group;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Stmt;

class Enum_Test extends \PHPUnit\Framework\TestCase
{
    protected function createEnumBuilder($class)
    {
        return new Enum_($class);
    }

    public function testImplements(): void
    {
        $node = $this->createEnumBuilder('SomeEnum')
            ->implement('Namespaced\SomeInterface', new Name('OtherInterface'))
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Enum_('SomeEnum', [
                'implements' => [
                    new Name('Namespaced\SomeInterface'),
                    new Name('OtherInterface'),
                ],
            ]),
            $node
        );
    }

    public function testSetScalarType(): void
    {
        $node = $this->createEnumBuilder('Test')
            ->set_scalar_type('int')
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Enum_('Test', [
                'scalarType' => new Identifier('int'),
            ]),
            $node
        );
    }

    public function testStatementOrder(): void
    {
        $method = new Stmt\Class_Method('testMethod');
        $enumCase = new Stmt\Enum_Case(
            'TEST_ENUM_CASE'
        );
        $const = new Stmt\Class_Const([
            new Node\Const_('TEST_CONST', new Node\Scalar\String_('ABC')),
        ]);
        $use = new Stmt\Trait_Use([new Name('SomeTrait')]);

        $node = $this->createEnumBuilder('Test')
            ->add_stmt($method)
            ->add_stmt($enumCase)
            ->add_stmts([$const, $use])
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Enum_('Test', [
                'stmts' => [$use, $enumCase, $const, $method],
            ]),
            $node
        );
    }

    public function testDocComment(): void
    {
        $docComment = <<<'DOC'
/**
 * Test
 */
DOC;
        $enum = $this->createEnumBuilder('Test')
            ->set_doc_comment($docComment)
            ->get_node();

        $this->assertEquals(
            new Stmt\Enum_('Test', [], [
                'comments' => [
                    new Comment\Doc($docComment),
                ],
            ]),
            $enum
        );

        $enum = $this->createEnumBuilder('Test')
            ->set_doc_comment(new Comment\Doc($docComment))
            ->get_node();

        $this->assertEquals(
            new Stmt\Enum_('Test', [], [
                'comments' => [
                    new Comment\Doc($docComment),
                ],
            ]),
            $enum
        );
    }

    public function testAddAttribute(): void
    {
        $attribute = new Attribute(
            new Name('Attr'),
            [new Arg(new Int_(1), false, false, [], new Identifier('name'))]
        );
        $attributeGroup = new Attribute_Group([$attribute]);

        $enum = $this->createEnumBuilder('ATTR_GROUP')
            ->add_attribute($attributeGroup)
            ->get_node();

        $this->assertEquals(
            new Stmt\Enum_('ATTR_GROUP', [
                'attrGroups' => [
                    $attributeGroup,
                ],
            ], []),
            $enum
        );
    }

    public function testInvalidStmtError(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unexpected node of type "PropertyItem"');
        $this->createEnumBuilder('Test')
            ->add_stmt(new Node\Property_Item('property'))
        ;
    }

    public function testInvalidDocComment(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Doc comment must be a string or an instance of Php_Parser\Comment\Doc');
        $this->createEnumBuilder('Test')
            ->set_doc_comment(new Comment('Test'));
    }

    public function testEmptyName(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Name cannot be empty');
        $this->createEnumBuilder('Test')
            ->implement('');
    }

    public function testInvalidName(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Name must be a string or an instance of Node\Name');
        $this->createEnumBuilder('Test')
            ->implement(['Foo']);
    }
}
