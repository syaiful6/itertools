<?php
namespace Itertools\Test;

use PHPUnit_Framework_TestCase;
use Itertools\ProductIterator;
use function Itertools\to_array;
use function Itertools\iter;
use function Itertools\range;

class ProductIteratorTest extends PHPUnit_Framework_TestCase
{
    public function testCartesianProductIterable()
    {
        $product = new ProductIterator(str_split('ABCD'), str_split('xy'));
        $dumped = to_array($product);
        $this->assertSame([['A','x'], ['A', 'y'], ['B', 'x'], ['B', 'y'],
            ['C', 'x'], ['C', 'y'], ['D', 'x'], ['D', 'y']], $dumped
        );

        $cartesian = new ProductIterator(\range(0, 2));
        $cartesian->setRepeat(3);
        $dumped = to_array($cartesian);
    }
}