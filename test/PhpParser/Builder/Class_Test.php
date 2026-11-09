<?php

declare(strict_types=1);

namespace Php_Parser\Builder;

use Php_Parser\Comment;
use Php_Parser\Modifiers;
use Php_Parser\Node;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Attribute;
use Php_Parser\Node\Attribute_Group;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Stmt;

class Class_Test extends \PHPUnit\Framework\TestCase
{
    protected function createClassBuilder($class)
    {
        return new Class_($class);
    }

    public function testExtendsImplements(): void
    {
        $node = $this->createClassBuilder('SomeLogger')
            ->extend('BaseLogger')
            ->implement('Namespaced\Logger', new Name('SomeInterface'))
            ->implement('\Fully\Qualified', 'namespace\NamespaceRelative')
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Class_('SomeLogger', [
                'extends' => new Name('BaseLogger'),
                'implements' => [
                    new Name('Namespaced\Logger'),
                    new Name('SomeInterface'),
                    new Name\Fully_Qualified('Fully\Qualified'),
                    new Name\Relative('NamespaceRelative'),
                ],
            ]),
            $node
        );
    }

    public function testAbstract(): void
    {
        $node = $this->createClassBuilder('Test')
            ->make_abstract()
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Class_('Test', [
                'flags' => Modifiers::ABSTRACT,
            ]),
            $node
        );
    }

    public function testFinal(): void
    {
        $node = $this->createClassBuilder('Test')
            ->make_final()
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Class_('Test', [
                'flags' => Modifiers::FINAL,
            ]),
            $node
        );
    }

    public function testReadonly(): void
    {
        $node = $this->createClassBuilder('Test')
            ->make_readonly()
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Class_('Test', [
                'flags' => Modifiers::READONLY,
            ]),
            $node
        );
    }

    public function testStatementOrder(): void
    {
        $method = new Stmt\Class_Method('testMethod');
        $property = new Stmt\Property(
            Modifiers::PUBLIC,
            [new Node\Property_Item('testProperty')]
        );
        $const = new Stmt\Class_Const([
            new Node\Const_('TEST_CONST', new Node\Scalar\String_('ABC')),
        ]);
        $use = new Stmt\Trait_Use([new Name('SomeTrait')]);

        $node = $this->createClassBuilder('Test')
            ->add_stmt($method)
            ->add_stmt($property)
            ->add_stmts([$const, $use])
            ->get_node()
        ;

        $this->assertEquals(
            new Stmt\Class_('Test', [
                'stmts' => [$use, $const, $property, $method],
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
        $class = $this->createClassBuilder('Test')
            ->set_doc_comment($docComment)
            ->get_node();

        $this->assertEquals(
            new Stmt\Class_('Test', [], [
                'comments' => [
                    new Comment\Doc($docComment),
                ],
            ]),
            $class
        );

        $class = $this->createClassBuilder('Test')
            ->set_doc_comment(new Comment\Doc($docComment))
            ->get_node();

        $this->assertEquals(
            new Stmt\Class_('Test', [], [
                'comments' => [
                    new Comment\Doc($docComment),
                ],
            ]),
            $class
        );
    }

    public function testAddAttribute(): void
    {
        $attribute = new Attribute(
            new Name('Attr'),
            [new Arg(new Int_(1), false, false, [], new Identifier('name'))]
        );
        $attributeGroup = new Attribute_Group([$attribute]);

        $class = $this->createClassBuilder('ATTR_GROUP')
            ->add_attribute($attributeGroup)
            ->get_node();

        $this->assertEquals(
            new Stmt\Class_('ATTR_GROUP', [
                'attr_groups' => [
                    $attributeGroup,
                ],
            ], []),
            $class
        );
    }

    public function testInvalidStmtError(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unexpected node of type "Stmt_Echo_"');
        $this->createClassBuilder('Test')
            ->add_stmt(new Stmt\Echo_([]))
        ;
    }

    public function testInvalidDocComment(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Doc comment must be a string or an instance of Php_Parser\Comment\Doc');
        $this->createClassBuilder('Test')
            ->set_doc_comment(new Comment('Test'));
    }

    public function testEmptyName(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Name cannot be empty');
        $this->createClassBuilder('Test')
            ->extend('');
    }

    public function testInvalidName(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Name must be a string or an instance of Node\Name');
        $this->createClassBuilder('Test')
            ->extend(['Foo']);
    }
}
