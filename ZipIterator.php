<?php
namespace Itertools;

use NoRewindIterator;
use MultipleIterator;
use Iterator;
/**
 * prevent call setFlags and make flags required on constructor
 */
class ZipInnerIterator extends MultipleIterator
{
    public function __construct()
    {
        parent::__construct(MultipleIterator::MIT_NEED_ALL|MultipleIterator::MIT_KEYS_NUMERIC);
    }

    /**
     *
     */
    public function setFlags($flags)
    {
    }
}

class ZipIterator extends NoRewindIterator
{
    /**
     *
     */
    public function __construct(Iterator ...$iterators)
    {
        $inner = new ZipInnerIterator();
        parent::__construct($inner);
        foreach ($iterators as $iterator) {
            $inner->attachIterator($iterator);
        }
    }

    /**
     * Attach an iterator to this iterator
     *
     * @param  \Iterator $iterator
     * @return void
     */
    public function attach(Iterator $iterator)
    {
        $inner = $this->getInnerIterator();
        $inner->attachIterator($iterator);
    }

    /**
     * Detach an iterator to this iterator
     *
     * @param  \Iterator $iterator
     * @return void
     */
    public function detach(Iterator $iterator)
    {
        $inner = $this->getInnerIterator();
        $inner->detachIterator($iterator);
    }
}
