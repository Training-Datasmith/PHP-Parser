<?php

declare(strict_types=1);

namespace Php_Parser;

class Dummy_Node extends Node_Abstract
{
    public $subNode1;
    public $subNode2;
    public $notSubNode;

    public function __construct($subNode1, $subNode2, $notSubNode, $attributes)
    {
        parent::__construct($attributes);
        $this->subNode1 = $subNode1;
        $this->subNode2 = $subNode2;
        $this->notSubNode = $notSubNode;
    }

    public function get_sub_node_names(): array
    {
        return ['subNode1', 'subNode2'];
    }

    // This method is only overwritten because the node is located in an unusual namespace
    public function get_type(): string
    {
        return 'Dummy';
    }
}

class Node_Abstract_Test extends \PHPUnit\Framework\TestCase
{
    public static function provideNodes()
    {
        $attributes = [
            'startLine' => 10,
            'endLine' => 11,
            'startTokenPos' => 12,
            'endTokenPos' => 13,
            'startFilePos' => 14,
            'endFilePos' => 15,
            'comments'  => [
                new Comment('// Comment 1' . "\n"),
                new Comment\Doc('/** doc comment */'),
                new Comment('// Comment 2' . "\n"),
            ],
        ];

        $node = new Dummy_Node('value1', 'value2', 'value3', $attributes);

        return [
            [$attributes, $node],
        ];
    }

    /**
     * @dataProvider provideNodes
     */
    public function testConstruct(array $attributes, Node $node)
    {
        $this->assertSame('Dummy', $node->get_type());
        $this->assertSame(['subNode1', 'subNode2'], $node->get_sub_node_names());
        $this->assertSame(10, $node->get_line());
        $this->assertSame(10, $node->get_start_line());
        $this->assertSame(11, $node->get_end_line());
        $this->assertSame(12, $node->get_start_token_pos());
        $this->assertSame(13, $node->get_end_token_pos());
        $this->assertSame(14, $node->get_start_file_pos());
        $this->assertSame(15, $node->get_end_file_pos());
        $this->assertSame('/** doc comment */', $node->get_doc_comment()->get_text());
        $this->assertSame('value1', $node->subNode1);
        $this->assertSame('value2', $node->subNode2);
        $this->assertTrue(isset($node->subNode1));
        $this->assertTrue(isset($node->subNode2));
        $this->assertTrue(!isset($node->subNode3));
        $this->assertSame($attributes, $node->get_attributes());
        $this->assertSame($attributes['comments'], $node->get_comments());

        return $node;
    }

    /**
     * @dataProvider provideNodes
     */
    public function testGetDocComment(array $attributes, Node $node): void
    {
        $this->assertSame('/** doc comment */', $node->get_doc_comment()->get_text());
        $comments = $node->get_comments();

        array_splice($comments, 1, 1, []); // remove doc comment
        $node->set_attribute('comments', $comments);
        $this->assertNull($node->get_doc_comment());

        // Remove all comments.
        $node->set_attribute('comments', []);
        $this->assertNull($node->get_doc_comment());
    }

    public function testSetDocComment(): void
    {
        $node = new Dummy_Node(null, null, null, []);

        // Add doc comment to node without comments
        $docComment = new Comment\Doc('/** doc */');
        $node->set_doc_comment($docComment);
        $this->assertSame($docComment, $node->get_doc_comment());

        // Replace it
        $docComment = new Comment\Doc('/** doc 2 */');
        $node->set_doc_comment($docComment);
        $this->assertSame($docComment, $node->get_doc_comment());

        // Add docmment to node with other comments
        $c1 = new Comment('/* foo */');
        $c2 = new Comment('/* bar */');
        $docComment = new Comment\Doc('/** baz */');
        $node->set_attribute('comments', [$c1, $c2]);
        $node->set_doc_comment($docComment);
        $this->assertSame([$c1, $c2, $docComment], $node->get_attribute('comments'));

        // Replace doc comment that is not at the end.
        $newDocComment = new Comment\Doc('/** new baz */');
        $node->set_attribute('comments', [$c1, $docComment, $c2]);
        $node->set_doc_comment($newDocComment);
        $this->assertSame([$c1, $newDocComment, $c2], $node->get_attribute('comments'));
    }

    /**
     * @dataProvider provideNodes
     */
    public function testChange(array $attributes, Dummy_Node $node): void
    {
        // direct modification
        $node->subNode1 = 'newValue';
        $this->assertSame('newValue', $node->subNode1);

        // indirect modification
        $subNode = &$node->subNode1;
        $subNode = 'newNewValue';
        $this->assertSame('newNewValue', $node->subNode1);

        // removal
        unset($node->subNode1);
        $this->assertFalse(isset($node->subNode1));
    }

    /**
     * @dataProvider provideNodes
     */
    public function testIteration(array $attributes, Node $node): void
    {
        // Iteration is simple object iteration over properties,
        // not over subnodes
        $i = 0;
        foreach ($node as $key => $value) {
            if ($i === 0) {
                $this->assertSame('subNode1', $key);
                $this->assertSame('value1', $value);
            } elseif ($i === 1) {
                $this->assertSame('subNode2', $key);
                $this->assertSame('value2', $value);
            } elseif ($i === 2) {
                $this->assertSame('notSubNode', $key);
                $this->assertSame('value3', $value);
            } else {
                throw new \Exception();
            }
            $i++;
        }
        $this->assertSame(3, $i);
    }

    public function testAttributes(): void
    {
        /** @var $node Node */
        $node = $this->getMockForAbstractClass(Node_Abstract::class);

        $this->assertEmpty($node->get_attributes());

        $node->set_attribute('key', 'value');
        $this->assertTrue($node->has_attribute('key'));
        $this->assertSame('value', $node->get_attribute('key'));

        $this->assertFalse($node->has_attribute('doesNotExist'));
        $this->assertNull($node->get_attribute('doesNotExist'));
        $this->assertSame('default', $node->get_attribute('doesNotExist', 'default'));

        $node->set_attribute('null', null);
        $this->assertTrue($node->has_attribute('null'));
        $this->assertNull($node->get_attribute('null'));
        $this->assertNull($node->get_attribute('null', 'default'));

        $this->assertSame(
            [
                'key'  => 'value',
                'null' => null,
            ],
            $node->get_attributes()
        );

        $node->set_attributes(
            [
                'a' => 'b',
                'c' => null,
            ]
        );
        $this->assertSame(
            [
                'a' => 'b',
                'c' => null,
            ],
            $node->get_attributes()
        );
    }

    public function testJsonSerialization(): void
    {
        $code = <<<'PHP'
<?php
// comment
/** doc comment */
function functionName(&$a = 0, $b = 1.0) {
    echo 'Foo';
}
PHP;
        $expected = <<<'JSON'
[
    {
        "nodeType": "Stmt_Function",
        "byRef": false,
        "name": {
            "nodeType": "Identifier",
            "name": "functionName",
            "attributes": {
                "startLine": 4,
                "startTokenPos": 7,
                "startFilePos": 45,
                "endLine": 4,
                "endTokenPos": 7,
                "endFilePos": 56
            }
        },
        "params": [
            {
                "nodeType": "Param",
                "type": null,
                "byRef": true,
                "variadic": false,
                "var": {
                    "nodeType": "Expr_Variable",
                    "name": "a",
                    "attributes": {
                        "startLine": 4,
                        "startTokenPos": 10,
                        "startFilePos": 59,
                        "endLine": 4,
                        "endTokenPos": 10,
                        "endFilePos": 60
                    }
                },
                "default": {
                    "nodeType": "Scalar_Int",
                    "value": 0,
                    "attributes": {
                        "startLine": 4,
                        "startTokenPos": 14,
                        "startFilePos": 64,
                        "endLine": 4,
                        "endTokenPos": 14,
                        "endFilePos": 64,
                        "rawValue": "0",
                        "kind": 10
                    }
                },
                "flags": 0,
                "attrGroups": [],
                "hooks": [],
                "attributes": {
                    "startLine": 4,
                    "startTokenPos": 9,
                    "startFilePos": 58,
                    "endLine": 4,
                    "endTokenPos": 14,
                    "endFilePos": 64
                }
            },
            {
                "nodeType": "Param",
                "type": null,
                "byRef": false,
                "variadic": false,
                "var": {
                    "nodeType": "Expr_Variable",
                    "name": "b",
                    "attributes": {
                        "startLine": 4,
                        "startTokenPos": 17,
                        "startFilePos": 67,
                        "endLine": 4,
                        "endTokenPos": 17,
                        "endFilePos": 68
                    }
                },
                "default": {
                    "nodeType": "Scalar_Float",
                    "value": 1,
                    "attributes": {
                        "startLine": 4,
                        "startTokenPos": 21,
                        "startFilePos": 72,
                        "endLine": 4,
                        "endTokenPos": 21,
                        "endFilePos": 74,
                        "rawValue": "1.0"
                    }
                },
                "flags": 0,
                "attrGroups": [],
                "hooks": [],
                "attributes": {
                    "startLine": 4,
                    "startTokenPos": 17,
                    "startFilePos": 67,
                    "endLine": 4,
                    "endTokenPos": 21,
                    "endFilePos": 74
                }
            }
        ],
        "returnType": null,
        "stmts": [
            {
                "nodeType": "Stmt_Echo",
                "exprs": [
                    {
                        "nodeType": "Scalar_String",
                        "value": "Foo",
                        "attributes": {
                            "startLine": 5,
                            "startTokenPos": 28,
                            "startFilePos": 88,
                            "endLine": 5,
                            "endTokenPos": 28,
                            "endFilePos": 92,
                            "kind": 1,
                            "rawValue": "'Foo'"
                        }
                    }
                ],
                "attributes": {
                    "startLine": 5,
                    "startTokenPos": 26,
                    "startFilePos": 83,
                    "endLine": 5,
                    "endTokenPos": 29,
                    "endFilePos": 93
                }
            }
        ],
        "attrGroups": [],
        "attributes": {
            "startLine": 4,
            "startTokenPos": 5,
            "startFilePos": 36,
            "endLine": 6,
            "endTokenPos": 31,
            "endFilePos": 95,
            "comments": [
                {
                    "nodeType": "Comment",
                    "text": "\/\/ comment",
                    "line": 2,
                    "filePos": 6,
                    "tokenPos": 1,
                    "endLine": 2,
                    "endFilePos": 15,
                    "endTokenPos": 1
                },
                {
                    "nodeType": "Comment_Doc",
                    "text": "\/** doc comment *\/",
                    "line": 3,
                    "filePos": 17,
                    "tokenPos": 3,
                    "endLine": 3,
                    "endFilePos": 34,
                    "endTokenPos": 3
                }
            ]
        }
    }
]
JSON;
        $expected81 = <<<'JSON'
[
    {
        "nodeType": "Stmt_Function",
        "attributes": {
            "startLine": 4,
            "startTokenPos": 5,
            "startFilePos": 36,
            "endLine": 6,
            "endTokenPos": 31,
            "endFilePos": 95,
            "comments": [
                {
                    "nodeType": "Comment",
                    "text": "\/\/ comment",
                    "line": 2,
                    "filePos": 6,
                    "tokenPos": 1,
                    "endLine": 2,
                    "endFilePos": 15,
                    "endTokenPos": 1
                },
                {
                    "nodeType": "Comment_Doc",
                    "text": "\/** doc comment *\/",
                    "line": 3,
                    "filePos": 17,
                    "tokenPos": 3,
                    "endLine": 3,
                    "endFilePos": 34,
                    "endTokenPos": 3
                }
            ]
        },
        "byRef": false,
        "name": {
            "nodeType": "Identifier",
            "attributes": {
                "startLine": 4,
                "startTokenPos": 7,
                "startFilePos": 45,
                "endLine": 4,
                "endTokenPos": 7,
                "endFilePos": 56
            },
            "name": "functionName"
        },
        "params": [
            {
                "nodeType": "Param",
                "attributes": {
                    "startLine": 4,
                    "startTokenPos": 9,
                    "startFilePos": 58,
                    "endLine": 4,
                    "endTokenPos": 14,
                    "endFilePos": 64
                },
                "type": null,
                "byRef": true,
                "variadic": false,
                "var": {
                    "nodeType": "Expr_Variable",
                    "attributes": {
                        "startLine": 4,
                        "startTokenPos": 10,
                        "startFilePos": 59,
                        "endLine": 4,
                        "endTokenPos": 10,
                        "endFilePos": 60
                    },
                    "name": "a"
                },
                "default": {
                    "nodeType": "Scalar_Int",
                    "attributes": {
                        "startLine": 4,
                        "startTokenPos": 14,
                        "startFilePos": 64,
                        "endLine": 4,
                        "endTokenPos": 14,
                        "endFilePos": 64,
                        "rawValue": "0",
                        "kind": 10
                    },
                    "value": 0
                },
                "flags": 0,
                "attrGroups": [],
                "hooks": []
            },
            {
                "nodeType": "Param",
                "attributes": {
                    "startLine": 4,
                    "startTokenPos": 17,
                    "startFilePos": 67,
                    "endLine": 4,
                    "endTokenPos": 21,
                    "endFilePos": 74
                },
                "type": null,
                "byRef": false,
                "variadic": false,
                "var": {
                    "nodeType": "Expr_Variable",
                    "attributes": {
                        "startLine": 4,
                        "startTokenPos": 17,
                        "startFilePos": 67,
                        "endLine": 4,
                        "endTokenPos": 17,
                        "endFilePos": 68
                    },
                    "name": "b"
                },
                "default": {
                    "nodeType": "Scalar_Float",
                    "attributes": {
                        "startLine": 4,
                        "startTokenPos": 21,
                        "startFilePos": 72,
                        "endLine": 4,
                        "endTokenPos": 21,
                        "endFilePos": 74,
                        "rawValue": "1.0"
                    },
                    "value": 1
                },
                "flags": 0,
                "attrGroups": [],
                "hooks": []
            }
        ],
        "returnType": null,
        "stmts": [
            {
                "nodeType": "Stmt_Echo",
                "attributes": {
                    "startLine": 5,
                    "startTokenPos": 26,
                    "startFilePos": 83,
                    "endLine": 5,
                    "endTokenPos": 29,
                    "endFilePos": 93
                },
                "exprs": [
                    {
                        "nodeType": "Scalar_String",
                        "attributes": {
                            "startLine": 5,
                            "startTokenPos": 28,
                            "startFilePos": 88,
                            "endLine": 5,
                            "endTokenPos": 28,
                            "endFilePos": 92,
                            "kind": 1,
                            "rawValue": "'Foo'"
                        },
                        "value": "Foo"
                    }
                ]
            }
        ],
        "attrGroups": []
    }
]
JSON;

        if (version_compare(PHP_VERSION, '8.1', '>=')) {
            $expected = $expected81;
        }

        $parser = new Parser\Php7(new Lexer());
        $stmts = $parser->parse(canonicalize($code));
        $json = json_encode($stmts, JSON_PRETTY_PRINT);
        $this->assertEquals(canonicalize($expected), canonicalize($json));
    }
}
