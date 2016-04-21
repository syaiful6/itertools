<?php
namespace Itertools\Test;

use PHPUnit_Framework_TestCase;
use Itertools\MapIterator;
use function Itertools\to_array;

class MapIteratorTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testMapOneIterable()
    {
        $celsius = [39.2, 36.5, 37.3, 37.8];
        $lambda = function ($x) {
            return (9/5) * $x + 32;
        };
        $fahrenheit = new MapIterator($lambda, $celsius);
        $fahrenheit = to_array($fahrenheit);
        $this->assertNotSame($celsius, $fahrenheit);
    }

    /**
     *
     */
    public function testMapMoreIterable()
    {
        $lambda = function (...$args) {
            return call_user_func('array_sum', $args);
        };

        $a = [1,2,3,4];
        $b = [17,12,11,10];
        $c = [-1,-4,5,9];

        $twoIterable = new MapIterator($lambda, $a, $b);
        $threeIterable = new MapIterator($lambda, $a, $b, $c);

        $this->assertSame([18, 14, 14, 14], to_array($twoIterable));
        $this->assertSame([17, 10, 19, 23], to_array($threeIterable));
    }
}
