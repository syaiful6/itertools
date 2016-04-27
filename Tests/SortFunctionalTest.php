<?php
namespace Itertools\Tests;

use PHPUnit_Framework_TestCase;
use Itertools\Tests\Utils\ArrayGenerator;
use function Itertools\sort;

class SortFunctionalTest extends PHPUnit_Framework_TestCase
{
    public function numberCompare($a, $b)
    {
        return $a - $b;
    }

    protected function _runTestFor($callback, $message, $comparator = null)
    {
        $lengths = [10, 100, 100, 10000];
        if ($comparator === null) {
            $comparator = [$this, 'numberCompare'];
        }
        foreach ($lengths as $len) {
            $arr1 = $callback($len);
            $arr2 = array_map(null, $arr1);
            $arr1 = sort($arr1, $comparator);
            usort($arr2, $comparator);
            $this->assertEquals($arr2, $arr1, sprintf($message, $len));
        }
    }

    /**
     *
     */
    public function testRandomArray()
    {
        $this->_runTestFor([ArrayGenerator::class, 'randomInt'],
            "Should sort a size %d random array");
    }

    /**
     *
     */
    public function testDescendingArray()
    {
        $this->_runTestFor([ArrayGenerator::class, 'descendingInt'],
            "Should sort a size %d descending array");
    }

    /**
     *
     */
    public function testAscendingArray()
    {
        $this->_runTestFor([ArrayGenerator::class, 'ascendingInt'],
            "Should sort a size %d ascending array");
    }

    /**
     *
     */
    public function testAscending3RandomExchangesArray()
    {
        $this->_runTestFor([ArrayGenerator::class, 'ascending3RandomExchangesInt'],
            "Should sort a size %d ascending array");
    }

    /**
     *
     */
    public function testAllEqualIntArray()
    {
        $this->_runTestFor([ArrayGenerator::class, 'allEqualInt'],
            "Should sort a size %d ascending array");
    }

    /**
     *
     */
    public function testStringStringAscendingIntegerSortByNatural()
    {
         $this->_runTestFor(function ($len) {
            return ArrayGenerator::randomStringAscendingInteger('Orange', $len);
         }, "Should sort a size %d ascending array", "strnatcmp");
    }

    /**
     *
     */
    public function testTimSortStability()
    {
        // use multidimensional array to see the stability
        $data = [['red', 1], ['blue', 1], ['red', 2], ['blue', 2]];
        $sorted = sort($data, function ($a, $b) {
            return strcmp($a[0], $b[0]);
        });
        $expected = [['blue', 1], ['blue', 2], ['red', 1], ['red', 2]];
        $this->assertEquals($expected, $sorted, 'Should stable');
    }
}
