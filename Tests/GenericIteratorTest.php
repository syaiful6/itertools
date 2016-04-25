<?php

namespace Itertools\Tests;

use PHPUnit_Framework_TestCase;
use Mockery as m;
use function Itertools\take_while;
use function Itertools\iter;
use function Itertools\multiple;
use function Itertools\chain;
use function Itertools\reversed;
use function Itertools\filter;
use function Itertools\filter_false;
use function Itertools\to_array;

class GenericIteratorTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /**
     *
     */
    public function testTakeWhile()
    {
        $input = [1,4,6,4,1];
        $take = take_while(function ($item) {
            return $item < 5;
        }, $input);
        $taken = to_array($take);
        $this->assertSame([1, 4], $taken);
    }

    /**
     *
     */
    public function testFilterIterator()
    {
        $toFilter = [0,1,2,3,4,5];
        $callback = function ($i) {
            return $i <= 3;
        };
        $filtered = to_array(filter($callback, $toFilter));
        $this->assertSame([0, 1, 2, 3], $filtered);
        $filtered = to_array(filter_false($callback, $toFilter));
        $this->assertSame([4, 5], $filtered);
    }

    /**
     *
     */
    public function tesMultiple()
    {
        $mul = to_array(multiple([1, 2, 3], 2));
        $this->assertSame(6, count($mul));
        $zero = to_array(multiple([1, 2, 3]), 0);
        $this->assertSame(0, count($zero));
        $oneWithIterator = to_array(multiple(new \ArrayIterator([1, 2, 3]), 1));
        $this->assertSame(3, count($oneWithIterator));
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
