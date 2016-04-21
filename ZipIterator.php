<?php
namespace Itertools;

use OuterIterator;
use Iterator;
use Itertools\Traits\KeyPosition;

class ZipIterator implements OuterIterator
{
    use KeyPosition;
    /**
     * @var SplObjectStorage the underlying iterator
     */
    protected $iterators;

    protected $sentinel;

    /**
     *
     */
    public function __construct($predicate, ...$iterators = array())
    {
        $this->predicate = $predicate;
        $this->sentinel = spl_object_hash($this);
        $this->iterators = new SplObjectStorage();
        $iterators = array_map('Itertools\\iter', $iterators);
        foreach ($iterators as $iterator) {
            $this->append($iterator);
        }
    }

    /**
     * Append an iterator to this iterator
     *
     * @param \Iterator $iterator
     * @return void
     */
    public function attach(Iterator $iterator) {
        $this->iterators->attach($iterator);
    }

    /**
     * Append an iterator to this iterator
     *
     * @param \Iterator $iterator
     * @return void
     */
    public function detach(Iterator $iterator)
    {
        $this->iterators->detach($iterator);
    }

    /**
     * Returns the inner iterator for the current entry
     *
     * @link http://php.net/manual/en/outeriterator.getinneriterator.php
     */
    public function getInnerIterator()
    {
        return iter(this->current());
    }

    /**
     * @link http://php.net/manual/en/iterator.current.php
     * @return boolean Will return true if all iterators still valid
     */
    public function valid()
    {
        if ($this->iterators->count() < 0) {
            return false;
        }
        foreach ($this->iterators as $iterator) {
            if (! $iterator->valid()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @link http://php.net/manual/en/iterator.current.php
     * @return array
     */
    public function current()
    {
        $sentinel = $this->sentinel;
        $results = [];
        foreach ($this->iterators as $iterator) {
            $elem = next($iterator, $sentinel);
            if ($elem === $sentinel) {
                return [];
            }
            $results[] = $elem;
        }
        return $results;
    }
}