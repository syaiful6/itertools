<?php
namespace Itertools\Sorting;

use SplFixedArray;
use Exception;
/**
 * Default minimum size of a run.
 */
const DEFAULT_MIN_MERGE = 32;
/**
 * Minimum ordered subsequece required to do galloping.
 */
const DEFAULT_MIN_GALLOPING = 7;
/**
 * Default tmp storage length. Can increase depending on the size of the
 * smallest run to merge.
 */
const DEFAULT_TMP_STORAGE_LENGTH = 256;

/**
 * Default alphabetical comparison of items.
 *
 * @param {string|object|number} a - First element to compare.
 * @param {string|object|number} b - Second element to compare.
 * @return {number} - A positive number if a.toString() > b.toString(), a
 * negative number if .toString() < b.toString(), 0 otherwise.
 */

function _sortDirectCompare($a, $b)
{
    if ($a === $b) {
        return 0;
    } else {
        return $a < $b ? -1 : 1;
    }
}

/**
 * Compute minimum run length for TimSort
 *
 * @param {number} n - The size of the array to sort.
 */

function minRunLength($n)
{
    $r = 0;
    while ($n >= DEFAULT_MIN_MERGE) {
        $r |= ($n & 1);
        $n >>= 1;
    }

    return $n + $r;
}

/**
 * Counts the length of a monotonically ascending or strictly monotonically
 * descending sequence (run) starting at array[lo] in the range [lo, hi). If
 * the run is descending it is made ascending.
 *
 * @param {array} array - The array to reverse.
 * @param {number} lo - First element in the range (inclusive).
 * @param {number} hi - Last element in the range.
 * @param {function} compare - Item comparison function.
 * @return {number} - The length of the run.
 */

function makeAscendingRun(&$array, $lo, $hi, $compare)
{
    $runHi = $lo + 1;
    if ($runHi === $hi) {
        return 1;
    }

    // Descending

    if ($compare($array[$runHi++], $array[$lo]) < 0) {
        while ($runHi < $hi && $compare($array[$runHi], $array[$runHi - 1]) < 0) {
            $runHi++;
        }

        reverseRun($array, $lo, $runHi);

        // Ascending
    } else {
        while ($runHi < $hi && $compare($array[$runHi], $array[$runHi - 1]) >= 0) {
            $runHi++;
        }
    }

    return $runHi - $lo;
}

/**
 * Reverse an array in the range [lo, hi).
 *
 * @param {array} array - The array to reverse.
 * @param {number} lo - First element in the range (inclusive).
 * @param {number} hi - Last element in the range.
 */

function reverseRun(&$array, $lo, $hi)
{
    $hi--;
    while ($lo < $hi) {
        $t = $array[$lo];
        $array[$lo++] = $array[$hi];
        $array[$hi--] = $t;
    }
}

/**
 * Perform the binary sort of the array in the range [lo, hi) where start is
 * the first element possibly out of order.
 *
 * @param {array} array - The array to sort.
 * @param {number} lo - First element in the range (inclusive).
 * @param {number} hi - Last element in the range.
 * @param {number} start - First element possibly out of order.
 * @param {function} compare - Item comparison function.
 */

function binaryInsertionSort(&$array, $lo, $hi, $start, $compare)
{
    if ($start === $lo) {
        $start++;
    }

    for (; $start < $hi; $start++) {
        $pivot = $array[$start];

        // Ranges of the array where pivot belongs

        $left = $lo;
        $right = $start;
        /*
        *   pivot >= array[i] for i in [lo, left)
        *   pivot <  array[i] for i in  in [right, start)
        */
        while ($left < $right) {
            $mid = (($left + $right) >> 1);
            if ($compare($pivot, $array[$mid]) < 0) {
                $right = $mid;
            } else {
                $left = $mid + 1;
            }
        }

        /*
        * Move elements right to make room for the pivot. If there are elements
        * equal to pivot, left points to the first slot after them: $this is also
        * a reason for which TimSort is stable
        */
        $n = $start - $left;

        // Switch is just an optimization for small arrays

        switch ($n) {
        case 3:
            $array[$left + 3] = $array[$left + 2];
            /* falls through */
        case 2:
            $array[$left + 2] = $array[$left + 1];
            /* falls through */
        case 1:
            $array[$left + 1] = $array[$left];
            break;

        default:
            while ($n > 0) {
                $array[$left + $n] = $array[$left + $n - 1];
                $n--;
            }
        }

        $array[$left] = $pivot;
    }
}

/**
 * Find the position at which to insert a value in a sorted range. If the range
 * contains elements equal to the value the leftmost element index is returned
 * (for stability).
 *
 * @param {number} value - Value to insert.
 * @param {array} array - The array in which to insert value.
 * @param {number} start - First element in the range.
 * @param {number} length - Length of the range.
 * @param {number} hint - The index at which to begin the search.
 * @param {function} compare - Item comparison function.
 * @return {number} - The index where to insert value.
 */

function gallopLeft($value, $array, $start, $length, $hint, $compare)
{
    $lastOffset = 0;
    $maxOffset = 0;
    $offset = 1;
    if ($compare($value, $array[$start + $hint]) > 0) {
        $maxOffset = $length - $hint;
        while ($offset < $maxOffset && $compare($value, $array[$start + $hint + $offset]) > 0) {
            $lastOffset = $offset;
            $offset = ($offset << 1) + 1;
            if ($offset <= 0) {
                $offset = $maxOffset;
            }
        }

        if ($offset > $maxOffset) {
            $offset = $maxOffset;
        }

        // Make offsets relative to start

        $lastOffset += $hint;
        $offset += $hint;

        // value <= array[start + hint]
    } else {
        $maxOffset = $hint + 1;
        while ($offset < $maxOffset && $compare($value, $array[$start + $hint - $offset]) <= 0) {
            $lastOffset = $offset;
            $offset = ($offset << 1) + 1;
            if ($offset <= 0) {
                $offset = $maxOffset;
            }
        }

        if ($offset > $maxOffset) {
            $offset = $maxOffset;
        }

        // Make offsets relative to start

        $tmp = $lastOffset;
        $lastOffset = $hint - $offset;
        $offset = $hint - $tmp;
    }

    /*
    * Now array[start+lastOffset] < value <= array[start+offset], so value
    * belongs somewhere in the range (start + lastOffset, start + offset]. Do a
    * binary search, with invariant array[start + lastOffset - 1] < value <=
    * array[start + offset].
    */
    $lastOffset++;
    while ($lastOffset < $offset) {
        $m = $lastOffset + (($offset - $lastOffset) >> 1);
        if ($compare($value, $array[$start + $m]) > 0) {
            $lastOffset = $m + 1;
        } else {
            $offset = $m;
        }
    }

    return $offset;
}

/**
 * Find the position at which to insert a value in a sorted range. If the range
 * contains elements equal to the value the rightmost element index is returned
 * (for stability).
 *
 * @param {number} value - Value to insert.
 * @param {array} array - The array in which to insert value.
 * @param {number} start - First element in the range.
 * @param {number} length - Length of the range.
 * @param {number} hint - The index at which to begin the search.
 * @param {function} compare - Item comparison function.
 * @return {number} - The index where to insert value.
 */

function gallopRight($value, $array, $start, $length, $hint, $compare)
{
    $lastOffset = 0;
    $maxOffset = 0;
    $offset = 1;
    if ($compare($value, $array[$start + $hint]) < 0) {
        $maxOffset = $hint + 1;
        while ($offset < $maxOffset && $compare($value, $array[$start + $hint - $offset]) < 0) {
            $lastOffset = $offset;
            $offset = ($offset << 1) + 1;
            if ($offset <= 0) {
                $offset = $maxOffset;
            }
        }

        if ($offset > $maxOffset) {
            $offset = $maxOffset;
        }

        // Make offsets relative to start

        $tmp = $lastOffset;
        $lastOffset = $hint - $offset;
        $offset = $hint - $tmp;

        // value >= array[start + hint]
    } else {
        $maxOffset = $length - $hint;
        while ($offset < $maxOffset && $compare($value, $array[$start + $hint + $offset]) >= 0) {
            $lastOffset = $offset;
            $offset = ($offset << 1) + 1;
            if ($offset <= 0) {
                $offset = $maxOffset;
            }
        }

        if ($offset > $maxOffset) {
            $offset = $maxOffset;
        }

        // Make offsets relative to start

        $lastOffset += $hint;
        $offset += $hint;
    }

    /*
    * Now array[start+lastOffset] < value <= array[start+offset], so value
    * belongs somewhere in the range (start + lastOffset, start + offset]. Do a
    * binary search, with invariant array[start + lastOffset - 1] < value <=
    * array[start + offset].
    */
    $lastOffset++;
    while ($lastOffset < $offset) {
        $m = $lastOffset + (($offset - $lastOffset) >> 1);
        if ($compare($value, $array[$start + $m]) < 0) {
            $offset = $m;
        } else {
            $lastOffset = $m + 1;
        }
    }

    return $offset;
}

class timsort
{
    public $array;
    public $tmp;

    protected $compare;
    protected $minGallop = DEFAULT_MIN_GALLOPING;
    protected $length = 0;
    protected $tmpStorageLength = DEFAULT_TMP_STORAGE_LENGTH;
    protected $stackLength = 0;
    protected $runStart;
    protected $runLength;
    protected $stackSize = 0;

    /**
     * param array $array
     * param callable $compare
     */
    public function __construct(&$array, callable $compare)
    {
        $this->array = $array;
        $this->compare = $compare;
        $this->length = \count($array);
        if ($this->length < 2 * DEFAULT_TMP_STORAGE_LENGTH) {
            $this->tmpStorageLength = $this->length >> 1;
        }

        $this->tmp = new SplFixedArray($this->tmpStorageLength);
        $this->stackLength = ($this->length < 120 ? 5 : ($this->length < 1542 ? 10 : ($this->length < 119151 ? 19 : 40)));
        $this->runStart = new SplFixedArray($this->stackLength);
        $this->runLength = new SplFixedArray($this->stackLength);
    }

    /**
     * Push a new run on TimSort's stack.
     *
     * @param number runStart - Start index of the run in the original array.
     * @param number runLength - Length of the run;
     */
    public function pushRun($runStart, $runLength)
    {
        $this->runStart[$this->stackSize] = $runStart;
        $this->runLength[$this->stackSize] = $runLength;
        $this->stackSize += 1;
    }

    /**
     * Merge runs on TimSort's stack so that the following holds for all i:
     * 1) runLength[i - 3] > runLength[i - 2] + runLength[i - 1]
     * 2) runLength[i - 2] > runLength[i - 1]
     */
    public function mergeRuns()
    {
        while ($this->stackSize > 1) {
            $n = $this->stackSize - 2;
            if (($n >= 1 && $this->runLength[$n - 1] <= $this->runLength[$n] + $this->runLength[$n + 1]) || ($n >= 2 && $this->runLength[$n - 2] <= $this->runLength[$n] + $this->runLength[$n - 1])) {
                if ($this->runLength[$n - 1] < $this->runLength[$n + 1]) {
                    $n--;
                }
            } elseif ($this->runLength[$n] > $this->runLength[$n + 1]) {
                break;
            }

            $this->mergeAt($n);
        }
    }

    /**
     * Merge all runs on TimSort's stack until only one remains.
     */
    public function forceMergeRuns()
    {
        while ($this->stackSize > 1) {
            $n = $this->stackSize - 2;
            if ($n > 0 && $this->runLength[$n - 1] < $this->runLength[$n + 1]) {
                $n--;
            }

            $this->mergeAt($n);
        }
    }

    /**
     * Merge the runs on the stack at positions i and i+1. Must be always be called
     * with i=stackSize-2 or i=stackSize-3 (that is, we merge on top of the stack).
     *
     * @param number $i - Index of the run to merge in TimSort's stack.
     */
    public function mergeAt($i)
    {
        $compare = $this->compare;
        $array = $this->array;
        $start1 = $this->runStart[$i];
        $length1 = $this->runLength[$i];
        $start2 = $this->runStart[$i + 1];
        $length2 = $this->runLength[$i + 1];
        $this->runLength[$i] = $length1 + $length2;
        if ($i === $this->stackSize - 3) {
            $this->runStart[$i + 1] = $this->runStart[$i + 2];
            $this->runLength[$i + 1] = $this->runLength[$i + 2];
        }

        $this->stackSize--;
        /*
        * Find where the first element in the second run goes in run1. Previous
        * elements in run1 are already in place
        */
        $k = gallopRight($array[$start2], $array, $start1, $length1, 0, $compare);
        $start1 += $k;
        $length1 -= $k;
        if ($length1 === 0) {
            return;
        }

        /*
        * Find where the last element in the first run goes in run2. Next elements
        * in run2 are already in place
        */
        $length2 = gallopLeft($array[$start1 + $length1 - 1], $array, $start2, $length2, $length2 - 1, $compare);
        if ($length2 === 0) {
            return;
        }

        /*
        * Merge remaining runs. A tmp array with length = min(length1, length2) is
        * used
        */
        if ($length1 <= $length2) {
            $this->mergeLow($start1, $length1, $start2, $length2);
        } else {
            $this->mergeHigh($start1, $length1, $start2, $length2);
        }
    }

    /**
     * Merge two adjacent runs in a stable way. The runs must be such that the
     * first element of run1 is bigger than the first element in run2 and the
     * last element of run1 is greater than all the elements in run2.
     * The method should be called when run1.length <= run2.length as it uses
     * TimSort temporary array to store run1. Use mergeHigh if run1.length >
     * run2.length.
     *
     * @param number $start1  - First element in run1.
     * @param number $length1 - Length of run1.
     * @param number $start2  - First element in run2.
     * @param number $length2 - Length of run2.
     */
    public function mergeLow($start1, $length1, $start2, $length2)
    {
        $this->ensureCapacity($length1);
        $compare = $this->compare;
        $array = & $this->array;
        $tmp = & $this->tmp;
        for ($i = 0; $i < $length1; $i++) {
            $tmp[$i] = $array[$start1 + $i];
        }

        $cursor1 = 0;
        $cursor2 = $start2;
        $dest = $start1;
        $array[$dest++] = $array[$cursor2++];
        if (--$length2 === 0) {
            for ($i = 0; $i < $length1; $i++) {
                $array[$dest + $i] = $tmp[$cursor1 + $i];
            }

            return;
        }

        if ($length1 === 1) {
            for ($i = 0; $i < $length2; $i++) {
                $array[$dest + $i] = $array[$cursor2 + $i];
            }

            $array[$dest + $length2] = $tmp[$cursor1];

            return;
        }

        $minGallop = $this->minGallop;
        while (true) {
            $count1 = 0;
            $count2 = 0;
            $exit = false;
            do {
                if ($compare($array[$cursor2], $tmp[$cursor1]) < 0) {
                    $array[$dest++] = $array[$cursor2++];
                    $count2++;
                    $count1 = 0;
                    if (--$length2 === 0) {
                        $exit = true;
                        break;
                    }
                } else {
                    $array[$dest++] = $tmp[$cursor1++];
                    $count1++;
                    $count2 = 0;
                    if (--$length1 === 1) {
                        $exit = true;
                        break;
                    }
                }
            } while (($count1 | $count2) < $minGallop);
            if ($exit) {
                break;
            }

            do {
                $count1 = gallopRight($array[$cursor2], $tmp, $cursor1, $length1, 0, $compare);
                if ($count1 !== 0) {
                    for ($i = 0; $i < $count1; $i++) {
                        $array[$dest + $i] = $tmp[$cursor1 + $i];
                    }

                    $dest += $count1;
                    $cursor1 += $count1;
                    $length1 -= $count1;
                    if ($length1 <= 1) {
                        $exit = true;
                        break;
                    }
                }

                $array[$dest++] = $array[$cursor2++];
                if (--$length2 === 0) {
                    $exit = true;
                    break;
                }

                $count2 = gallopLeft($tmp[$cursor1], $array, $cursor2, $length2, 0, $compare);
                if ($count2 !== 0) {
                    for ($i = 0; $i < $count2; $i++) {
                        $array[$dest + $i] = $array[$cursor2 + $i];
                    }

                    $dest += $count2;
                    $cursor2 += $count2;
                    $length2 -= $count2;
                    if ($length2 === 0) {
                        $exit = true;
                        break;
                    }
                }

                $array[$dest++] = $tmp[$cursor1++];
                if (--$length1 === 1) {
                    $exit = true;
                    break;
                }

                $minGallop--;
            } while ($count1 >= DEFAULT_MIN_GALLOPING || $count2 >= DEFAULT_MIN_GALLOPING);
            if ($exit) {
                break;
            }

            if ($minGallop < 0) {
                $minGallop = 0;
            }

            $minGallop += 2;
        }

        $this->minGallop = $minGallop;
        if ($minGallop < 1) {
            $this->minGallop = 1;
        }

        if ($length1 === 1) {
            for ($i = 0; $i < $length2; $i++) {
                $array[$dest + $i] = $array[$cursor2 + $i];
            }

            $array[$dest + $length2] = $tmp[$cursor1];
        } elseif ($length1 === 0) {
            throw new Exception('mergeLow preconditions were not respected');
        } else {
            for ($i = 0; $i < $length1; $i++) {
                $array[$dest + $i] = $tmp[$cursor1 + $i];
            }
        }
    }

    /**
     * Merge two adjacent runs in a stable way. The runs must be such that the
     * first element of run1 is bigger than the first element in run2 and the
     * last element of run1 is greater than all the elements in run2.
     * The method should be called when run1.length > run2.length as it uses
     * TimSort temporary array to store run2. Use mergeLow if run1.length <=
     * run2.length.
     *
     * @param number start1 - First element in run1.
     * @param number length1 - Length of run1.
     * @param number start2 - First element in run2.
     * @param number length2 - Length of run2.
     */
    public function mergeHigh($start1, $length1, $start2, $length2)
    {
        $this->ensureCapacity($length2);
        $compare = $this->compare;
        $array = & $this->array;
        $tmp = & $this->tmp;
        for ($i = 0; $i < $length2; $i++) {
            $tmp[$i] = $array[$start2 + $i];
        }

        $cursor1 = $start1 + $length1 - 1;
        $cursor2 = $length2 - 1;
        $dest = $start2 + $length2 - 1;
        $customCursor = 0;
        $customDest = 0;
        $array[$dest--] = $array[$cursor1--];
        if (--$length1 === 0) {
            $customCursor = $dest - ($length2 - 1);
            for ($i = 0; $i < $length2; $i++) {
                $array[$customCursor + $i] = $tmp[$i];
            }

            return;
        }

        if ($length2 === 1) {
            $dest -= $length1;
            $cursor1 -= $length1;
            $customDest = $dest + 1;
            $customCursor = $cursor1 + 1;
            for ($i = $length1 - 1; $i >= 0; $i--) {
                $array[$customDest + $i] = $array[$customCursor + $i];
            }

            $array[$dest] = $tmp[$cursor2];

            return;
        }

        $minGallop = $this->minGallop;
        while (true) {
            $count1 = 0;
            $count2 = 0;
            $exit = false;
            do {
                if ($compare($tmp[$cursor2], $array[$cursor1]) < 0) {
                    $array[$dest--] = $array[$cursor1--];
                    $count1++;
                    $count2 = 0;
                    if (--$length1 === 0) {
                        $exit = true;
                        break;
                    }
                } else {
                    $array[$dest--] = $tmp[$cursor2--];
                    $count2++;
                    $count1 = 0;
                    if (--$length2 === 1) {
                        $exit = true;
                        break;
                    }
                }
            } while (($count1 | $count2) < $minGallop);
            if ($exit) {
                break;
            }

            do {
                $count1 = $length1 - gallopRight($tmp[$cursor2], $array, $start1, $length1, $length1 - 1, $compare);
                if ($count1 !== 0) {
                    $dest -= $count1;
                    $cursor1 -= $count1;
                    $length1 -= $count1;
                    $customDest = $dest + 1;
                    $customCursor = $cursor1 + 1;
                    for ($i = $count1 - 1; $i >= 0; $i--) {
                        $array[$customDest + $i] = $array[$customCursor + $i];
                    }

                    if ($length1 === 0) {
                        $exit = true;
                        break;
                    }
                }

                $array[$dest--] = $tmp[$cursor2--];
                if (--$length2 === 1) {
                    $exit = true;
                    break;
                }

                $count2 = $length2 - gallopLeft($array[$cursor1], $tmp, 0, $length2, $length2 - 1, $compare);
                if ($count2 !== 0) {
                    $dest -= $count2;
                    $cursor2 -= $count2;
                    $length2 -= $count2;
                    $customDest = $dest + 1;
                    $customCursor = $cursor2 + 1;
                    for ($i = 0; $i < $count2; $i++) {
                        $array[$customDest + $i] = $tmp[$customCursor + $i];
                    }

                    if ($length2 <= 1) {
                        $exit = true;
                        break;
                    }
                }

                $array[$dest--] = $array[$cursor1--];
                if (--$length1 === 0) {
                    $exit = true;
                    break;
                }

                $minGallop--;
            } while ($count1 >= DEFAULT_MIN_GALLOPING || $count2 >= DEFAULT_MIN_GALLOPING);
            if ($exit) {
                break;
            }

            if ($minGallop < 0) {
                $minGallop = 0;
            }

            $minGallop += 2;
        }

        $this->minGallop = $minGallop;
        if ($minGallop < 1) {
            $this->minGallop = 1;
        }

        if ($length2 === 1) {
            $dest -= $length1;
            $cursor1 -= $length1;
            $customDest = $dest + 1;
            $customCursor = $cursor1 + 1;
            for ($i = $length1 - 1; $i >= 0; $i--) {
                $array[$customDest + $i] = $array[$customCursor + $i];
            }

            $array[$dest] = $tmp[$cursor2];
        } elseif ($length2 === 0) {
            throw new Exception('mergeHigh preconditions were not respected');
        } else {
            $customCursor = $dest - ($length2 - 1);
            for ($i = 0; $i < $length2; $i++) {
                $array[$customCursor + $i] = $tmp[$i];
            }
        }
    }

    protected function ensureCapacity($minCapacity)
    {
        if ($this->tmp->getSize() < $minCapacity) {
            $newSize = $minCapacity;
            $newSize |= $newSize >> 1;
            $newSize |= $newSize >> 2;
            $newSize |= $newSize >> 4;
            $newSize |= $newSize >> 8;
            $newSize |= $newSize >> 16;
            $newSize++;
            if ($newSize < 0) {
                $newSize = $minCapacity;
            } else {
                $newSize = min($newSize, count($this->array) >> 1);
            }
            // increase
            $this->tmp->setSize($newSize);
        }
    }
}

/**
 * Sort an array in the range [lo, hi) using TimSort.
 *
 * @param $array array - The array to sort.
 * @param callable compare - Item comparison function. Default is
 *     alphabetical
 */

function timsort(&$array, callable $compare = null)
{
    if ($compare === null) {
        $compare = __NAMESPACE__.'\\_sortDirectCompare';
    }
    $runLength = $lo = 0;
    $remaining = $hi = \count($array);

    // On small arrays binary sort can be used directly
    if ($remaining < DEFAULT_MIN_MERGE) {
        $runLength = makeAscendingRun($array, $lo, $hi, $compare);
        binaryInsertionSort($array, $lo, $hi, $lo + $runLength, $compare);

        return;
    }

    $ts = new TimSort($array, $compare);
    $minRun = minRunLength($remaining);
    do {
        $runLength = makeAscendingRun($ts->array, $lo, $hi, $compare);
        if ($runLength < $minRun) {
            $force = $remaining;
            if ($force > $minRun) {
                $force = $minRun;
            }

            binaryInsertionSort($ts->array, $lo, $lo + $force, $lo + $runLength, $compare);
            $runLength = $force;
        }

        // Push new run and merge if necessary

        $ts->pushRun($lo, $runLength);
        $ts->mergeRuns();

        // Go find next run

        $remaining -= $runLength;
        $lo += $runLength;
    } while ($remaining !== 0);

    // Force merging of remaining runs

    $ts->forceMergeRuns();
    $array = $ts->array;
}
