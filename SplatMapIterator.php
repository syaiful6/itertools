<?php
namespace Itertools;

use IteratorIterator;
use Traversable;

class SplatMapIterator extends IteratorIterator
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     *
     */
    public function __construct(callable $callback, Traversable $traversable)
    {
        parent::__construct($traversable);
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
