<?php
namespace Itertools\Sorting;

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

/**
 * Sort an array in the range [lo, hi) using TimSort.
 *
 * @param $array array - The array to sort.
 * @param callable compare - Item comparison function. Default is
 *     alphabetical
 */

function timsort(&$array, callable $compare = null, $lo = null, $hi = null)
{
    if ($compare === null) {
        $compare = __NAMESPACE__.'\\_sortDirectCompare';
    }
    if ($lo === null) {
        $lo = 0;
    }
    if ($hi === null) {
        $hi = \count($array);
    }
    $remaining = $hi - $lo;
    // The array is already sorted
    if ($remaining < 2) {
        return;
    }
    $runLength = 0;

    // On small arrays binary sort can be used directly
    if ($remaining < DEFAULT_MIN_MERGE) {
        $runLength = makeAscendingRun($array, $lo, $hi, $compare);
        binaryInsertionSort($array, $lo, $hi, $lo + $runLength, $compare);

        return;
    }

    $ts = new TimSortMergeState($array, $compare);
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

    $ts->forceMergeRuns();
    $array = $ts->array;
}
