<?php

declare(strict_types=1);

namespace Php_Parser\Node\Scalar;

use Php_Parser\Node\Stmt\Echo_;
use Php_Parser\Parser_Factory;

class Number_Test extends \PHPUnit\Framework\TestCase
{
    public function testRawValue(): void
    {
        $parser = (new Parser_Factory())->create_for_newest_supported_version();
        $nodes = $parser->parse('<?php echo 1_234;');

        $echo = $nodes[0];
        $this->assertInstanceOf(Echo_::class, $echo);

        /** @var Echo_ $echo */
        $lnumber = $echo->exprs[0];
        $this->assertInstanceOf(Int_::class, $lnumber);

        /** @var Int_ $lnumber */
        $this->assertSame(1234, $lnumber->value);
        $this->assertSame('1_234', $lnumber->get_attribute('rawValue'));
    }
}
