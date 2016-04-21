<?php
namespace Itertools;

use IteratorIterator;

/**
 * Apply function to every item of iterable and return a list of the results.
 */
class MapIterator extends IteratorIterator
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     *
     */
    public function __construct(callable $callback, ...$iterables)
    {
        parent::__construct(zip(...$iterables));
        $this->callback = $callback;
    }

    /**
     *
     */
    public function current()
    {
        $callback = $this->callback;
        $item = parent::current();

        return $callback(...$item);
    }
}
