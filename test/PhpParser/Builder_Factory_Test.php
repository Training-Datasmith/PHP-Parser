<?php

declare(strict_types=1);

namespace Php_Parser;

use Php_Parser\Node\Arg;
use Php_Parser\Node\Attribute;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Binary_Op\Concat;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Scalar\String_;

class Builder_Factory_Test extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provideTestFactory
     */
    public function testFactory($methodName, $className): void
    {
        $factory = new Builder_Factory();
        $this->assertInstanceOf($className, $factory->$methodName('test'));
    }

    public static function provideTestFactory()
    {
        return [
            ['namespace',   Builder\Namespace_::class],
            ['class',       Builder\Class_::class],
            ['interface',   Builder\Interface_::class],
            ['trait',       Builder\Trait_::class],
            ['enum',        Builder\Enum_::class],
            ['method',      Builder\Method::class],
            ['function',    Builder\Function_::class],
            ['property',    Builder\Property::class],
            ['param',       Builder\Param::class],
            ['use',         Builder\Use_::class],
            ['use_function', Builder\Use_::class],
            ['use_const',    Builder\Use_::class],
            ['enum_case',    Builder\Enum_Case::class],
        ];
    }

    public function testFactoryClassConst(): void
    {
        $factory = new Builder_Factory();
        $this->assertInstanceOf(Builder\Class_Const::class, $factory->class_const('TEST', 1));
    }

    public function testAttribute(): void
    {
        $factory = new Builder_Factory();
        $this->assertEquals(
            new Attribute(new Name('AttributeName'), [new Arg(
                new String_('bar'),
                false,
                false,
                [],
                new Identifier('foo')
            )]),
            $factory->attribute('AttributeName', ['foo' => 'bar'])
        );
    }

    public function testVal(): void
    {
        // This method is a wrapper around Builder_Helpers::normalize_value(),
        // which is already tested elsewhere
        $factory = new Builder_Factory();
        $this->assertEquals(
            new String_('foo'),
            $factory->val('foo')
        );
    }

    public function testConcat(): void
    {
        $factory = new Builder_Factory();
        $varA = new Expr\Variable('a');
        $varB = new Expr\Variable('b');
        $varC = new Expr\Variable('c');

        $this->assertEquals(
            new Concat($varA, $varB),
            $factory->concat($varA, $varB)
        );
        $this->assertEquals(
            new Concat(new Concat($varA, $varB), $varC),
            $factory->concat($varA, $varB, $varC)
        );
        $this->assertEquals(
            new Concat(new Concat(new String_('a'), $varB), new String_('c')),
            $factory->concat('a', $varB, 'c')
        );
    }

    public function testConcatOneError(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected at least two expressions');
        (new Builder_Factory())->concat('a');
    }

    public function testConcatInvalidExpr(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected string or Expr');
        (new Builder_Factory())->concat('a', 42);
    }

    public function testArgs(): void
    {
        $factory = new Builder_Factory();
        $unpack = new Arg(new Expr\Variable('c'), false, true);
        $this->assertEquals(
            [
                new Arg(new Expr\Variable('a')),
                new Arg(new String_('b')),
                $unpack,
            ],
            $factory->args([new Expr\Variable('a'), 'b', $unpack])
        );
    }

    public function testNamedArgs(): void
    {
        $factory = new Builder_Factory();
        $this->assertEquals(
            [
                new Arg(new String_('foo')),
                new Arg(new String_('baz'), false, false, [], new Identifier('bar')),
            ],
            $factory->args(['foo', 'bar' => 'baz'])
        );
    }

    public function testCalls(): void
    {
        $factory = new Builder_Factory();

        // Simple function call
        $this->assertEquals(
            new Expr\Func_Call(
                new Name('var_dump'),
                [new Arg(new String_('str'))]
            ),
            $factory->func_call('var_dump', ['str'])
        );
        // Dynamic function call
        $this->assertEquals(
            new Expr\Func_Call(new Expr\Variable('fn')),
            $factory->func_call(new Expr\Variable('fn'))
        );

        // Simple method call
        $this->assertEquals(
            new Expr\Method_Call(
                new Expr\Variable('obj'),
                new Identifier('method'),
                [new Arg(new Int_(42))]
            ),
            $factory->method_call(new Expr\Variable('obj'), 'method', [42])
        );
        // Explicitly pass Identifier node
        $this->assertEquals(
            new Expr\Method_Call(
                new Expr\Variable('obj'),
                new Identifier('method')
            ),
            $factory->method_call(new Expr\Variable('obj'), new Identifier('method'))
        );
        // Dynamic method call
        $this->assertEquals(
            new Expr\Method_Call(
                new Expr\Variable('obj'),
                new Expr\Variable('method')
            ),
            $factory->method_call(new Expr\Variable('obj'), new Expr\Variable('method'))
        );

        // Simple static method call
        $this->assertEquals(
            new Expr\Static_Call(
                new Name\Fully_Qualified('Foo'),
                new Identifier('bar'),
                [new Arg(new Expr\Variable('baz'))]
            ),
            $factory->static_call('\Foo', 'bar', [new Expr\Variable('baz')])
        );
        // Dynamic static method call
        $this->assertEquals(
            new Expr\Static_Call(
                new Expr\Variable('foo'),
                new Expr\Variable('bar')
            ),
            $factory->static_call(new Expr\Variable('foo'), new Expr\Variable('bar'))
        );

        // Simple new call
        $this->assertEquals(
            new Expr\New_(new Name\Fully_Qualified('stdClass')),
            $factory->new('\stdClass')
        );
        // Dynamic new call
        $this->assertEquals(
            new Expr\New_(
                new Expr\Variable('foo'),
                [new Arg(new String_('bar'))]
            ),
            $factory->new(new Expr\Variable('foo'), ['bar'])
        );
    }

    public function testConst_Fetches(): void
    {
        $factory = new Builder_Factory();
        $this->assertEquals(
            new Expr\Const_Fetch(new Name('FOO')),
            $factory->const_fetch('FOO')
        );
        $this->assertEquals(
            new Expr\Class_Const_Fetch(new Name('Foo'), new Identifier('BAR')),
            $factory->class_const_fetch('Foo', 'BAR')
        );
        $this->assertEquals(
            new Expr\Class_Const_Fetch(new Expr\Variable('foo'), new Identifier('BAR')),
            $factory->class_const_fetch(new Expr\Variable('foo'), 'BAR')
        );
        $this->assertEquals(
            new Expr\Class_Const_Fetch(new Name('Foo'), new Expr\Variable('foo')),
            $factory->class_const_fetch('Foo', $factory->var('foo'))
        );
    }

    public function testVar(): void
    {
        $factory = new Builder_Factory();
        $this->assertEquals(
            new Expr\Variable('foo'),
            $factory->var('foo')
        );
        $this->assertEquals(
            new Expr\Variable(new Expr\Variable('foo')),
            $factory->var($factory->var('foo'))
        );
    }

    public function testProperty_Fetch(): void
    {
        $f = new Builder_Factory();
        $this->assertEquals(
            new Expr\Property_Fetch(new Expr\Variable('foo'), 'bar'),
            $f->property_fetch($f->var('foo'), 'bar')
        );
        $this->assertEquals(
            new Expr\Property_Fetch(new Expr\Variable('foo'), 'bar'),
            $f->property_fetch($f->var('foo'), new Identifier('bar'))
        );
        $this->assertEquals(
            new Expr\Property_Fetch(new Expr\Variable('foo'), new Expr\Variable('bar')),
            $f->property_fetch($f->var('foo'), $f->var('bar'))
        );
    }

    public function testInvalidIdentifier(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected string or instance of Node\Identifier');
        (new Builder_Factory())->class_const_fetch('Foo', new Name('foo'));
    }

    public function testInvalidIdentifierOrExpr(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected string or instance of Node\Identifier or Node\Expr');
        (new Builder_Factory())->static_call('Foo', new Name('bar'));
    }

    public function testInvalidNameOrExpr(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Name must be a string or an instance of Node\Name or Node\Expr');
        (new Builder_Factory())->func_call(new Node\Stmt\Return_());
    }

    public function testInvalidVar(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Variable name must be string or Expr');
        (new Builder_Factory())->var(new Node\Stmt\Return_());
    }

    public function testIntegration(): void
    {
        $factory = new Builder_Factory();
        $node = $factory->namespace('Name\Space')
            ->add_stmt($factory->use('Foo\Bar\SomeOtherClass'))
            ->add_stmt($factory->use('Foo\Bar')->as('A'))
            ->add_stmt($factory->use_function('strlen'))
            ->add_stmt($factory->use_const('PHP_VERSION'))
            ->add_stmt($factory
                ->class('SomeClass')
                ->extend('SomeOtherClass')
                ->implement('A\Few', '\Interfaces')
                ->add_attribute($factory->attribute('ClassAttribute', ['repository' => 'fqcn']))
                ->make_abstract()

                ->add_stmt($factory->use_trait('FirstTrait'))

                ->add_stmt($factory->use_trait('SecondTrait', 'ThirdTrait')
                    ->and('AnotherTrait')
                    ->with($factory->trait_use_adaptation('foo')->as('bar'))
                    ->with($factory->trait_use_adaptation('AnotherTrait', 'baz')->as('test'))
                    ->with($factory->trait_use_adaptation('AnotherTrait', 'func')->insteadof('SecondTrait')))

                ->add_stmt(
                    $factory->method('firstMethod')
                    ->add_attribute($factory->attribute('Route', ['/index', 'name' => 'homepage']))
                )

                ->add_stmt($factory->method('someMethod')
                    ->make_public()
                    ->make_abstract()
                    ->add_param($factory->param('someParam')->set_type('SomeClass'))
                    ->set_doc_comment('/**
                                      * This method does something.
                                      *
                                      * @param SomeClass And takes a parameter
                                      */'))

                ->add_stmt($factory->method('anotherMethod')
                    ->make_protected()
                    ->add_param(
                        $factory->param('someParam')
                        ->set_default('test')
                        ->add_attribute($factory->attribute('TaggedIterator', ['app.handlers']))
                    )
                    ->add_stmt(new Expr\Print_(new Expr\Variable('someParam'))))

                ->add_stmt($factory->property('someProperty')->make_protected())
                ->add_stmt($factory->property('anotherProperty')
                    ->make_private()
                    ->set_default([1, 2, 3]))
                ->add_stmt($factory->property('integerProperty')
                    ->set_type('int')
                    ->add_attribute($factory->attribute('Column', ['options' => ['unsigned' => true]]))
                    ->set_default(1))
                ->add_stmt(
                    $factory->class_const('CONST_WITH_ATTRIBUTE', 1)
                    ->make_public()
                    ->add_attribute($factory->attribute('ConstAttribute'))
                )

                ->add_stmt($factory->class_const('FIRST_CLASS_CONST', 1)
                    ->add_const('SECOND_CLASS_CONST', 2)
                    ->make_private()))
            ->get_node()
        ;

        $expected = <<<'EOC'
<?php

namespace Name\Space;

use Foo\Bar\SomeOtherClass;
use Foo\Bar as A;
use function strlen;
use const PHP_VERSION;
#[ClassAttribute(repository: 'fqcn')]
abstract class SomeClass extends SomeOtherClass implements A\Few, \Interfaces
{
    use FirstTrait;
    use SecondTrait, ThirdTrait, AnotherTrait {
        foo as bar;
        AnotherTrait::baz as test;
        AnotherTrait::func insteadof SecondTrait;
    }
    #[ConstAttribute]
    public const CONST_WITH_ATTRIBUTE = 1;
    private const FIRST_CLASS_CONST = 1, SECOND_CLASS_CONST = 2;
    protected $someProperty;
    private $anotherProperty = [1, 2, 3];
    #[Column(options: ['unsigned' => true])]
    public int $integerProperty = 1;
    #[Route('/index', name: 'homepage')]
    function firstMethod()
    {
    }
    /**
     * This method does something.
     *
     * @param SomeClass And takes a parameter
     */
    abstract public function someMethod(SomeClass $someParam);
    protected function anotherMethod(
        #[TaggedIterator('app.handlers')]
        $someParam = 'test'
    )
    {
        print $someParam;
    }
}
EOC;

        $stmts = [$node];
        $prettyPrinter = new Pretty_Printer\Standard();
        $generated = $prettyPrinter->pretty_print_file($stmts);

        $this->assertEquals(
            str_replace("\r\n", "\n", $expected),
            str_replace("\r\n", "\n", $generated)
        );
    }
}
