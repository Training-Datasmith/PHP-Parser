<?php

declare(strict_types=1);

namespace Php_Parser\Node\Scalar;

use Php_Parser\Node\Stmt\Echo_;
use Php_Parser\Parser_Factory;

class D_Number_Test extends \PHPUnit\Framework\TestCase
{
    public function testRawValue(): void
    {
        $parser = (new Parser_Factory())->create_for_newest_supported_version();
        $nodes = $parser->parse('<?php echo 1_234.56;');

        $echo = $nodes[0];
        $this->assertInstanceOf(Echo_::class, $echo);

        /** @var Echo_ $echo */
        $dnumber = $echo->exprs[0];
        $this->assertInstanceOf(Float_::class, $dnumber);

        /** @var Float_ $dnumber */
        $this->assertSame(1234.56, $dnumber->value);
        $this->assertSame('1_234.56', $dnumber->get_attribute('rawValue'));
    }
}
