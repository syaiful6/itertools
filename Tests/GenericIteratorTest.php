<?php

namespace Itertools\Test;

use PHPUnit_Framework_TestCase;
use Mockery as m;
use function Itertools\iter, Itertools\chain, Itertools\range, Itertools\reversed,
    Itertools\next, Itertools\to_array;

class GenericIteratorTest extends PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        m::close();
    }
    /**
     *
     */
    public function testChainGenerator()
    {
        $chained = chain(['a', 'b', 'c'], ['d', 'e', 'f']);
        $expected = ['a', 'b', 'c', 'd', 'e', 'f'];
        $this->assertSame($expected, to_array($chained));
    }

    /**
     *
     */
    public function testReversedFuncReceiveReverseable()
    {
        $reverseable = m::mock('Itertools\Reverseable');
        $reverseable->shouldReceive('reversed')->once()
            ->andReturn(iter(['foo', 'bar']));

        $reverse = reversed($reverseable);
        $this->assertTrue($reverse instanceof \ArrayIterator);
        $this->assertSame(['foo', 'bar'], to_array($reverse));

    }

    /**
     *
     */
    public function testReversedFuncNonReverseable()
    {
        $array = ['a', 'b', 'c'];
        $reverse = reversed($array);
        $this->assertSame(['c', 'b', 'a'], to_array($reverse));
    }
}
