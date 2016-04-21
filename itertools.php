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
        return new \ArrayIterator($iterable);
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
        $value = $iterator->current();
        $iterator->next();

        return $value;
    }
    if ($default !== null) {
        return $default;
    }
    throw new StopIteration(sprintf(
        '%s iterator no longer valid to iterate',
        get_class($iterator)
    ));
}

/**
 *
 */
function zip(...$iterators)
{
    $iterators = array_map('Itertools\\iter', $iterators);
    return new ZipIterator(...$iterators);
}

/**
 *
 */
function reversed($seq)
{
    if ($seq instanceof Reverseable) {
        return $seq->reversed();
    }
    return call_user_func(function () use ($seq){
        $length = \count($seq);
        foreach (range($length - 1, -1, -1) as $index) {
            yield $seq[$index];
        }
    });
}

/**
 * reduce iterable to a single value. because the array_reduce only support array
 */
function reduce(callable $function, $iterable, $startValue = null)
{
    $acc = $startValue;
    foreach ($iterable as $item) {
        $acc = $function($acc, $item);
    }
    return $acc;
}

/**
 * Joins the elements of an iterable with a separator between them.
 *
 */
function join($separator, $iterable) {
    $str = '';
    $first = true;
    foreach ($iterable as $value) {
        if ($first) {
            $str .= $value;
            $first = false;
        } else {
            $str .= $separator . $value;
        }
    }
    return $str;
}

/**
 *
 */
function all(callable $callback, $iterable)
{
    foreach ($iterable as $it) {
        if (! $callback($it)) {
            return false;
        }
    }
    return true;
}

/**
 *
 */
function any(callable $callback, $iterable)
{
    $iter = iterable($iterable);
    foreach ($iter as $it) {
        if ($callback($it)) {
            return true;
        }
    }
    return false;
}

/**
 *
 */
function map($callback, ...$iterators)
{
    if ($callback === null) {
        $callback = function (...$args) {
            return $args;
        };
    }

    return new MapIterator($callback, ...$iterators);
}

/**
 *
 */
function splatmap($callback, $iterable)
{
    if ($callback === null) {
        $callback = function (...$args) {
            return $args;
        };
    }
    return new SplatMapIterator($callback, iter($iterable));
}

/**
 *
 */
function filter($callback, $iterable)
{
    $wrap = function ($current) use ($callback) {
        if ($callback === null) {
            return (bool) $current;
        }
        return $callback($current);
    };
    $iter = iter($iterable);
    return new \CallbackFilterIterator($iter, $callback);
}

/**
 *
 */
function filterfalse($callback, $iterable)
{
    $wrap = function ($current) use ($callback) {
        if ($callback === null) {
            return !$current;
        }
        return !$callback($current);
    };
    $iter = iter($iterable);
    return new \CallbackFilterIterator($iter, $callback);
}

/**
 * Generate an iterator that yield integer. this function is different from
 * PHP native range that immediately return array and the output members are also
 * different.
 * example:
 * php \range(1, 5) -> [1,2,3,4,5]
 * this function range(1,5) -> [1,2,3,4]
 *
 * @param integer $start if emitted default to zero
 * @param integer $stop
 * @param integer $step
 */
function range($start, $stop = null, $step = null)
{
    if ($stop === null) {
        $stop = $start ?: 0;
        $start = 0;
    }
    if ($step === null) {
        $step = $stop < $start ? -1 : 1;
    }
    if (! $step === 0) {
        throw new \InvalidArgumentException(
            'argument 3 passed to range cant be 0 (zero)'
        );
    }
    $length = max(ceil(($stop - $start) / $step), 0);
    for ($i = 0; $i < $length; $start += $step) {
        yield $start;
        $i++;
    }
}

/**
 * Make an iterator that returns accumulated sums. If the optional callback
 * argument is supplied, it should be a callable which accept two arguments
 * and it will be used instead of addition.
 */
function accumulate($iterable, callable $callback = null)
{
    if ($callback === null) {
        $callback = function($a, $b) {
            return $a + $b;
        };
    }
    $iter = iter($iterable);
    // used to identify
    $sentinel = new \stdClass;
    $total = next($iter, $sentinel);
    if ($total !== $sentinel) {
        yield $total;
        while ($sentinel !== ($elem = next($iter, $sentinel))) {
            $total = $callback($total, $elem);
            yield $total;
        }
    }
}

/**
 *
 */
function chain(...$iterables)
{
    foreach ($iterables as $iterable) {
        foreach ($iterable as $element) {
            yield $element;
        }
    }
}

function chain_from_iterable($iterable)
{
    foreach ($iterable as $it) {
        foreach ($it as $elem) {
            yield $elem;
        }
    }
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
function repeat($target, $times = null)
{
    if ($times === null) {
        while (true) {
            yield $target;
        }
    } else {
        foreach (range($times) as $_) {
            yield $target;
        }
    }
}

/**
 *
 */
function takewhile(callable $predicate, $iterable)
{
    foreach ($iterable as $elem) {
        if ($predicate($elem)) {
            yield $elem;
        } else {
            break;
        }
    }
}

/**
 *
 */
function dropwhile(callable $predicate, $iterable)
{
    $iter = iter($iterable);
    try {
        while (true) {
            $x = next($iter);
            if (! $predicate($x)) {
                yield $x;
            }
            break;
        }

        while (true) {
            yield next($iter);
        }
    } catch(StopIteration $e) {
        // pass
    }
}

/**
 *
 */
function groupby($iterable, callable $grouper = null)
{
    $iterator = new Groupby($iterable, $grouper);
    return iter($iterator);
}

/**
 *
 */
function slice($iterable, $start, $stop = null, $step = null)
{
    $start = $start ?: 0;
    $stop = $stop ?: INF;
    $step = $step ?: 1;
    $nexts = range($start, $stop, $step);
    $nexti = $nexts->current();
    foreach(enumerate($iterable, 0, true) as $key => list($i, $element)) {
        if ($i == $nexti) {
            yield $key => $element;
            $nexts->next();
            $nexti = $nexts->current();
        }
    }
}

/**
 *
 */
function enumerate($iterable, $start = 0, $preservekey = false)
{
    if (! $preservekey) {
        foreach ($iterable as $val) {
            yield $start => $val;
            $start++;
        }
    } else {
        foreach ($iterable as $key => $val) {
            yield $key => [$start, $val];
            $start++;
        }
    }
}

/**
 * Return n independent iterators from a single iterable, usefull when you
 * dealing with no rewind iterator, but need to traverse more that 1 time.
 */
function tee($iterable, $n = 2)
{
    $iter = iter($iterable);
    $deques = array_map(function ($_) {
        $queue = new \SplQueue();
        $queue->setIteratorMode(\SplQueue::IT_MODE_DELETE);

        return $queue;
    }, \range(0, $n - 1));

    return \array_map(function ($locdeque) use ($iter, $deques) {
        while (true) {
            // in case the local deque is empty we will fecth data from
            // original iterator, and pass them to all dequeues.
            if ($locdeque->isEmpty()) {
                if (! $iter->valid()) {
                    return;
                }
                $newval = $iter->current();
                foreach ($deques as $d) {
                    $d->enqueue($newval);
                }
                $iter->next();
            }
            yield $locdeque->dequeue();
        }
    }, $deques);
}

/**
 *
 */
function to_array($iterable, $preserve = false)
{
    if (is_array($iterable)) {
        return $iterable;
    }
    $out = [];
    if (!$preserve) {
        foreach (iter($iterable) as $value) {
            $out[] = $value;
        }
    } else {
        foreach (iter($iterable) as $key => $value) {
            $out[$key] = $value;
        }
    }
    return $out;
}

/**
 *
 */
function multiple($iterable, $by = 2)
{
    $count = \count($iterable);
    $maxLoop = $count * $by;
    return new \LimitIterator(cycle($iterable), $maxLoop);
}

/**
 * Return a new sorted array from the items in iterable.
 * This function not maintain the keys.
 *
 * @param array|\Traversable $iterable The source to sorted
 * @param boolean            $reverse  If set to true, then it sorted as descending
 * @param callable           $key      Specifies a function of one argument that
 *                                     is used to extract a comparison key from
 *                                     each iterable element. Default null,
 *                                     compare the elements directly
 * @return array
 */
function sorted($iterable, $reverse = false, callable $key = null)
{
    $arrays = to_array($iterable);
    if ($key === null) {
        $phpSorted = $reverse ? '\\rsort' : '\\sort';
        $phpSorted($arrays);

        return $arrays;
    } else {
        // maintain the keys first to get back them
        $phpSorted = $reverse ? '\\arsort' : '\\asort';
        $copy = \array_map($key, $arrays);
        $phpSorted($copy);
        $retval = [];
        foreach ($copy as $k => $v) {
            $retval[$k] = $arrays[$k];
        }
        // then remove the keys
        return \array_values($retval);
    }
}

function product(...$iterables)
{
    $iterators = array_map('Itertools\\iter', $iterables);
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
