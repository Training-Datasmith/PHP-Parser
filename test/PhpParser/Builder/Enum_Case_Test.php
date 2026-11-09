<?php

declare(strict_types=1);

namespace Php_Parser\Builder;

use Php_Parser\Comment;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Attribute;
use Php_Parser\Node\Attribute_Group;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Scalar;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Stmt;

class Enum_Case_Test extends \PHPUnit\Framework\TestCase
{
    public function create_enum_case_builder($name)
    {
        return new Enum_Case($name);
    }

    public function testDocComment(): void
    {
        $node = $this->create_enum_case_builder('TEST')
            ->set_doc_comment('/** Test */')
            ->get_node();

        $this->assertEquals(
            new Stmt\Enum_Case(
                'TEST',
                null,
                [],
                [
                    'comments' => [new Comment\Doc('/** Test */')],
                ]
            ),
            $node
        );
    }

    public function testAddAttribute(): void
    {
        $attribute = new Attribute(
            new Name('Attr'),
            [new Arg(new Int_(1), false, false, [], new Identifier('name'))]
        );
        $attributeGroup = new Attribute_Group([$attribute]);

        $node = $this->create_enum_case_builder('ATTR_GROUP')
            ->add_attribute($attributeGroup)
            ->get_node();

        $this->assertEquals(
            new Stmt\Enum_Case(
                'ATTR_GROUP',
                null,
                [$attributeGroup]
            ),
            $node
        );
    }

    /**
     * @dataProvider provideTestDefaultValues
     */
    public function testValues($value, $expectedValueNode): void
    {
        $node = $this->create_enum_case_builder('TEST')
            ->set_value($value)
            ->get_node()
        ;

        $this->assertEquals($expectedValueNode, $node->expr);
    }

    public static function provideTestDefaultValues()
    {
        return [
            [
                31415,
                new Scalar\Int_(31415),
            ],
            [
                'Hallo World',
                new Scalar\String_('Hallo World'),
            ],
        ];
    }
}
