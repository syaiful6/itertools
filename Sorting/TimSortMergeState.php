<?php
namespace Itertools\Sorting;

use SplFixedArray;
use InvalidArgumentException;

class TimSortMergeState
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
            throw new InvalidArgumentException(
                'Comparison method violates its general contract!');
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
            throw new InvalidArgumentException(
                'Comparison method violates its general contract!');
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