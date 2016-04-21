<?php

namespace Itertools\Test;

use PHPUnit_Framework_TestCase;
use function Itertools\zip;
use Itertools\tee;
use Itertools\next;

class ZipIteratorTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testZipIterationSameLength()
    {
        $zipped = zip([1, 2, 3], [4, 5, 6], [7, 8, 9]);
        $this->assertSame([1, 4, 7], next($zipped));
        $this->assertSame([2, 5, 8], next($zipped));
        $this->assertSame([3, 6, 9], next($zipped));
        $this->assertFalse($zipped->valid());
    }

    /**
     *
     */
    public function testZipDoesnotRewind()
    {
        list($first, $sec) = tee(str_split('syai'));
        $sec->next();
        $pairwise = zip($first, $sec);
        $this->assertSame(['s', 'y'], next($pairwise));
        $this->assertSame(['y', 'a'], next($pairwise));
        $this->assertSame(['a', 'i'], next($pairwise));
        $this->assertFalse($pairwise->valid());
    }

    /**
     *
     */
    public function testZipIterationNotSameLength()
    {
        $zipped = zip([1, 2, 3, 4], [4, 5, 6, 7, 8], [7, 8, 9]);
        $this->assertSame([1, 4, 7], next($zipped));
        $this->assertSame([2, 5, 8], next($zipped));
        $this->assertSame([3, 6, 9], next($zipped));
        $this->assertFalse($zipped->valid());
    }
}
