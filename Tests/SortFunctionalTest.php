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
    /**
     *
     */
    public function testRandomArray()
    {
        $lengths = [10, 100, 100, 10000];
        $repetitions = 10;
        foreach ($lengths as $len) {
            for ($i = 0; $i < $repetitions; $i++) {
                $arr1 = ArrayGenerator::randomInt($len);
                $arr2 = array_map(null, $arr1);
                $arr1 = sort($arr1, [$this, 'numberCompare']);
                usort($arr2, [$this, 'numberCompare']);
                $this->assertEquals($arr2, $arr1, "Should sort a size $len random array");
            }
        }
    }

    /**
     *
     */
    public function testDescendingArray()
    {
        $lengths = [10, 100, 1000, 10000];
        $repetitions = 10;
        foreach ($lengths as $len) {
            for ($i = 0; $i < $repetitions; $i++) {
                $arr1 = ArrayGenerator::descendingInt($len);
                $arr2 = array_map(null, $arr1);
                $arr1 = sort($arr1, [$this, 'numberCompare']);
                usort($arr2, [$this, 'numberCompare']);
                $this->assertEquals($arr2, $arr1, "Should sort a size $len descending array");
            }
        }
    }
}
