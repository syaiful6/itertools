<?php
namespace Itertools;

use IteratorAggregate;
use Itertools\Traits\SimpleIterable;

/**
 * Originally this cartesion product operation written in function generator,
 * but to allow user set the repeat option, we move it class based. so they can
 * set the repeat value before iterating.
 */
class ProductIterator implements IteratorAggregate
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
        $pools = array_map('Itertools\\to_array', $this->iterables);
        if ($repeat > 1) {
            $pools = to_array(multiple($pools, $repeat));
        }
        $result = [[]];
        foreach ($pools as $key => $values) {
            $append = [];
            foreach($result as $product) {
                foreach($values as $item) {
                    $product[$key] = $item;
                    $append[] = $product;
                }
            }
            $result = $append;
        }
        foreach ($result as $r) {
            yield $r;
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
