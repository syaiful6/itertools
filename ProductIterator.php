<?php
namespace Itertools;

use IteratorAggregate
use Itertools\Traits\SimpleIterable;

class Product implements IteratorAggregate
{
    use SimpleIterable;
    protected $repeat = 1;
    protected $iterables = [];

    /**
     *
     */
    public function __construct(...$iterables)
    {
        $this->iterables = $iterables;
    }

    /**
     *
     */
    public function setRepeat($repeat)
    {
        $this->repeat = (int) $repeat;
    }

    /**
     *
     */
    public function _innerIterator()
    {
        $repeat = $this->repeat;
        if ($repeat === 1) {
            $iterators = array_map('Itertools\\iter', $this->iterables);
        } else {
            $copied[];
            foreach ($this->iterables as $iterable) {
                $copied[] = tee($iterable, $repeat);
            }
            $iterators = to_array(chain(...zip(...$copied)));
        }
        $len = count($iterators);
        if (!$len) {
            yield [] => [];
            return;
        }
        $keyTuple = $valueTuple = array_fill(0, $len, null);
        $i = -1;
        while (true) {
            while (++$i < $len - 1) {
                $iterators[$i]->rewind();
                if (!$iterators[$i]->valid()) {
                    return;
                }
                $keyTuple[$i] = $iterators[$i]->key();
                $valueTuple[$i] = $iterators[$i]->current();
            }
            foreach ($iterators[$i] as $keyTuple[$i] => $valueTuple[$i]) {
                yield $keyTuple => $valueTuple;
            }
            while (--$i >= 0) {
                $iterators[$i]->next();
                if ($iterators[$i]->valid()) {
                    $keyTuple[$i] = $iterators[$i]->key();
                    $valueTuple[$i] = $iterators[$i]->current();
                    continue 2;
                }
            }
            return;
        }
    }

    /**
     *
     */
    public function getRepeat()
    {
        return $this->repeat;
    }
}
