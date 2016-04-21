<?php
namespace Itertools;

use Iterator;
use IteratorAggregate;
use Traversable;

/**
* Convert any PHP value to iterator if it can
*
* @param mixed $iterable
* @return \Iterator
* @throws \InvalidArgumentException
*/
function iter($iterable)
{
    if ($iterable instanceof Iterator) {
        return $iterable;
    }
    if ($iterable instanceof IteratorAggregate) {
        return $iterable->getIterator();
    }
    if (is_array($iterable)) {
        return \ArrayIterator($iterable);
    }
    throw new \InvalidArgumentException('Argument must be iterable');
}

/**
 * Retrieve the next item from the iterator, If default is given, it is returned
 * if the iterator is exhausted
 *
 * @param \Iterator $iterator
 * @param mixed $default
 * @return mixed
 */
function next($iterator, $default = null)
{
    if (! $iterator instanceof Iterator) {
        throw new \InvalidArgumentException(sprintf(
            'Argument 1 must be an iterator, %s give', gettype($iterator)
        ));
    }
    if ($iterator->valid()) {
        $iterator->next();
        return $iterator->current();
    }
    if ($default !== null) {
        return $default;
    }
    throw new new StopIteration('Iterator has no longer item to restrieve');
}

/**
 * create new CountIterator that iterate over returns evenly spaced values starting
 * number. Note, this iterator is infinite
 * example:
 * count()
 * -> [0, 1, 2, 3, 4...]
 * @see CountIterator
 */
function count($start = 0, $step = 1)
{
    return new CountIterator($start, $step);
}

/**
 * Cycle the item in iterable
 * cycle(['first', 'last']);
 * -> ['first', 'last', 'first', 'last', 'first', 'last'...]
 */
function cycle($iterable)
{
    $iter = iter($iterable);
    return new \InfiniteIterator($iter);
}

/**
 *
 */
function all($iterable, $callback = null)
{
    if ($callback === null) {
        $callback = function ($item) {
            return !$item;
        };
    }
    $iter = iter($iterable);
    foreach ($iter as $it) {
        if (! $callback($it)) {
            return false;
        }
    }
    return true;
}

/**
 *
 */
function any($iterable, $callback = null)
{
    if ($callback === null) {
        $callback = function ($item) {
            return (bool) $item;
        };
    }
    $iter = iterable($iterable);
    foreach ($iter as $it) {
        if ($callback($it)) {
            return true;
        }
    }
    return false;
}