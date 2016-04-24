<?php
namespace Itertools\Test;

use PHPUnit_Framework_TestCase;
use Itertools\ProductIterator;
use function Itertools\permutations;
use function Itertools\combinations_with_replacement;
use function Itertools\combinations;
use function Itertools\to_array;
use function Itertools\range;

class CombinatoricTest extends PHPUnit_Framework_TestCase
{
    public function testCartesianIterable()
    {
        $product = new ProductIterator(range(2));
        $product->setRepeat(3);
        $results = to_array($product);
        $this->assertSame(8, count($results));
        $expected = '000 001 010 011 100 101 110 111';
        $results = array_map(function ($d) {
            return join('', $d);
        }, $results);
        $this->assertSame(preg_split("/[\s,]+/", $expected), $results);
    }

    /**
     *
     */
    public function testPermutations()
    {
        $permutations = permutations(range(3));
        $results = to_array($permutations);
        $this->assertSame(6, count($results));
        $expected = '012 021 102 120 201 210';
        $results = array_map(function ($d) {
            return join('', $d);
        }, $results);
        $this->assertSame(preg_split("/[\s,]+/", $expected), $results);
    }

    /**
     *
     */
    public function testCombinations()
    {
        $combinations = combinations(range(4), 3);
        $results = to_array($combinations);
        $this->assertSame(4, count($results));
        $expected = '012 013 023 123';
        $results = array_map(function ($d) {
            return join('', $d);
        }, $results);
        $this->assertSame(preg_split("/[\s,]+/", $expected), $results);
    }

    /**
     *
     */
    public function testCombinationsWithReplacement()
    {
        $combinations = combinations_with_replacement(['A', 'B', 'C'], 2);
        $results = to_array($combinations);
        $this->assertSame(6, count($results));
        $expected = 'AA AB AC BB BC CC';
        $results = array_map(function ($d) {
            return join('', $d);
        }, $results);
        $this->assertSame(preg_split("/[\s,]+/", $expected), $results);
    }
}
