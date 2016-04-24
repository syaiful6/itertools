<?php
namespace Itertools;

use Iterator;

class CallbackFilterIterator extends FilterIterator
{
    /**
     *
     */
    public function __construct(callable $callback, Iterator $iterator)
    {
        $this->callback = $callback;
        parent::__construct($iterator);
    }

    /**
     *
     */
    public function accept()
    {
        $callback = $this->callback;
        return (bool) $callback($this->current(), $this->key(), $this->getInnerIterator());
    }
}
