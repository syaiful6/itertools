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
 * Make an iterator that aggregates elements from each of the iterables. Stop when
 * one of the iterable exhausted.
 *
 * @param mixed ...$iterable Stack of iterable
 * @return \Generator
 */
function zip(...$iterables)
{
    $iterators = \array_map(__NAMESPACE__.'\\iter', $iterables);
    $sentinel = new \stdClass();
    while (\count($iterators) > 0) {
        $results = [];
        foreach ($iterators as $it) {
            $elem = next($it, $sentinel);
            if ($elem === $sentinel) {
                return;
            }
            $results[] = $elem;
        }
        yield $results;
    }
}

/**
 * Make an iterator that aggregates elements from each of the iterables. Stop when
 * all of the iterable exhausted. See ZipLongest if you want to set the fillValue
 * for iterable that already exhausted.
 *
 * @param mixed ...$iterable Stack of iterable
 * @return ZipLongest
 */
function zip_longest(...$iterables)
{
    $iterators = \array_map(__NAMESPACE__.'\\iter', $iterables);

    return new ZipLongest(...$iterators);
}

/**
 * Return an iterator that reverse version passed here. The parameter passed here
 * should instanceof Itertools\Reverseable. Otherwise that object should countable
 * and implements ArrayAccess, where all member of this object accessed via numeric value.
 *
 * @param Itertools\Reverseable|array|Countable&\ArrayAccess
 */
function reversed($seq)
{
    if ($seq instanceof Reverseable) {
        return $seq->reversed();
    }
    if (is_array($seq) || ($seq instanceof \Countable && $seq instanceof \ArrayAccess)) {
        return call_user_func(function () use ($seq) {
            $length = \count($seq);
            foreach (range($length - 1, -1, -1) as $index) {
                yield $seq[$index];
            }
        });
    }
    throw new \InvalidArgumentException(sprintf(
        'Reversed expect argument 1 passed is instanceof %s, array, or instanceof Countable and Array Access',
        Reverseable::class
    ));
}

/**
 * Reduce iterable to a single value. because the array_reduce only support array
 *
 * @param callable $function
 * @param \Traversable|array $iterable
 * @param mixed $startValue
 * @return mixed
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
 * Make an iterator that filters elements from data returning only those that
 * have a corresponding element in selectors that evaluates to true. Stops when
 * either the data or selectors iterables has been exhausted.
 *
 * compress(['a', 'b', 'c', 'd', 'e', 'f'], [1,0,1,0,1,1])
 * --> ['a', 'c', 'e', 'f']
 */
function compress($data, $selector)
{
    foreach (zip($data, $selector) as list($d, $s)) {
        if ($s) {
            yield $d;
        }
    }
}

/**
 * Joins the elements of an iterable with a separator between them. This is just
 * like PHP builtin function implode and join except this function accept all iterable.
 *
 * @param string $separator
 * @param \Traversable|array
 * @return string
 */
function join($separator, $iterable)
{
    $str = '';
    $first = true;
    foreach ($iterable as $value) {
        if ($first) {
            $str .= $value;
            $first = false;
        } else {
            $str .= $separator.$value;
        }
    }

    return $str;
}

/**
 * Returns true if all values in the iterable satisfy the predicate.
 *
 * @param callable $callback If it null we will wrap it with identify Closue
 * @param \Traversable|array $iterable
 * @return boolean
 */
function all($callback, $iterable)
{
    if ($callback === null) {
        $callback = function ($item) {
            return (bool) $item;
        };
    }
    foreach ($iterable as $it) {
        if (! $callback($it)) {
            return false;
        }
    }

    return true;
}

/**
 * Returns true if there is a value in the iterable that satisfies the
 * predicate.
 *
 * @param callable $callback If it null we will wrap it with identify Closue
 * @param \Traversable|array $iterable
 * @return boolean
 */
function any($callback, $iterable)
{
    if ($callback === null) {
        $callback = function ($item) {
            return (bool) $item;
        };
    }
    foreach ($iterable as $it) {
        if ($callback($it)) {
            return true;
        }
    }

    return false;
}

/**
 *
 */
function map(callable $callback, ...$iterables)
{
    $iterables = zip(...$iterables);
    foreach ($iterables as $args) {
        yield $callback(...$args);
    }
}

/**
 *
 */
function splat_map(callable $callback, $iterable)
{
    foreach (iter($iterable) as $args) {
        yield $callback(...$args);
    }
}

/**
 *
 */
function filter(callable $callback, $iterable)
{
    $iter = iter($iterable);

    return new CallbackFilterIterator($callback, $iter);
}

/**
 *
 */
function filter_false(callable $callback, $iterable)
{
    $wrap = function (...$args) use ($callback) {
        return !$callback(...$args);
    };
    $iter = iter($iterable);

    return new CallbackFilterIterator($wrap, $iter);
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
function accumulate(callable $callback, $iterable)
{
    $iter = iter($iterable);
    // used to identify
    $sentinel = new \stdClass();
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
 * create iterator that iterate over returns evenly spaced values starting
 * number. Note, this iterator is infinite
 * example:
 * count()
 * -> [0, 1, 2, 3, 4...]
 * @see CountIterator
 */
function counter($start = 0, $step = 1)
{
    while (true) {
        yield $start;
        $start += $step;
    }
}

/**
 * Cycle the item in iterable
 * cycle(['first', 'last']);
 * -> ['first', 'last', 'first', 'last', 'first', 'last'...]
 */
function cycle($iterable)
{
    $saved = [];
    $iterable = iter($iterable);
    foreach ($iterable as $key => $elem) {
        yield $key => $elem;
        $saved[$key] = $elem;
    }
    while (\count($saved) > 0) {
        foreach ($saved as $key => $elem) {
            yield $key => $elem;
        }
    }
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
function take_while(callable $predicate, $iterable)
{
    $iter = iter($iterable);
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
function drop_while(callable $predicate, $iterable)
{
    $iter = iter($iterable);
    try {
        while (true) {
            $x = next($iter);
            if (! $predicate($x)) {
                yield $x;
                break;
            }
        }

        while (true) {
            yield next($iter);
        }
    } catch (StopIteration $e) {
        // pass
    }
}

/**
 *
 */
function groupby($grouper, $iterable)
{
    return new Groupby($grouper, $iterable);
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
    foreach (enumerate($iterable) as $key => list($i, $element)) {
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
function enumerate($iterable, $start = 0, $preservekey = true)
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
    if ($by === 0) {
        return;
    }
    $saved = [];
    foreach ($iterable as $k => $elem) {
        yield $k => $elem;
        $saved[$k] = $elem;
    }
    if ($by === 1) {
        return;
    }
    $count = \count($saved);
    $maxLoop = $count * $by;
    foreach (enumerate(cycle($saved), 2) as $key => list($k, $elem)) {
        if ($k > $maxLoop) {
            break;
        }
        yield $key => $elem;
    }
}

/**
 * Sort the iterable using TimSort algoritm, because PHP sort family function
 * isn't stable, ie same value maybe swapped.
 *
 * @param \Traversable|array|ArrayAccess Countable $iterable
 * @param callable|null $comparator, if not provided compare directly
 * @return array
 */
function sort($iterable, callable $comparator = null)
{
    if ($iterable instanceof \Countable && $iterable instanceof \ArrayAccess) {
        // dont convert to array because timsort only need to access using array
        // access using numerical key
        $array = $iterable;
    } elseif ($iterable instanceof \Traversable) {
        $array = to_array($iterable);
    } elseif (is_array($iterable)) {
        $array = $iterable;
    } else {
        throw new \InvalidArgumentException(sprintf(
            'Expect parameter 1 passed to %s to be an array, ArrayAccess & Countable or Traversable'
        ));
    }

    Sorting\timsort($array, $comparator);

    return $array;
}

/**
 * Sorts iterable using a set of keys by mapping the values in iterable through
 * the given callback. The implementation using Decorate-Sort-Undecorate idiom,
 * in decorate step we make multidimensional array containing the original
 * collection element and the mapped value. This makes fairly expensive when
 * the keysets are simple. This operation efficient for large array and lists
 * where the comparison information is expensive to calculate.
 *
 * consider:
 *   sort([1,2,3,4,5], function ($a, $b) {
 *      return User::find($a)->last_login <=> User::find($b)->last_login
 *   });
 *
 * it inefficient: it will generates two new User objects during every comparison.
 * use sort_by instead, because it will cache the user last_login before sorting.
 * Perl users often call this approach a Schwartzian Transform, after Randal Schwartz.
 */
function sort_by($iterable, callable $by)
{
    $decorated = map(function ($item) use ($by) {
        list($i, $v) = $item;
        return [$by($v), $i, $item];
    }, enumerate($iterable));
    // the comparator
    $comparator = function ($a, $b) {
        if ($a[0] === $b[0]) {
            // if two items have the same key, we should preserve their original order
            return $a[1] < $b[1] ? -1 : 1;
        } else {
            return $a[0] < $b[0] ? -1 : 1;
        }
    };
    $decorated = to_array($decorated);
    Sorting\timsort($decorated, $comparator);
    // undecorate
    return array_map(function ($item) {
        return $item[2];
    }, $decorated);
}

/**
 *
 */
function product(...$iterables)
{
    return new ProductIterator(...$iterables);
}

/**
 * Return successive r length permutations of elements in the iterable.
 * ex: permutations(['a', 'b', 'c', 'd'], 2)
 *
 */
function permutations($iterable, $r = null)
{
    $pools = to_array($iterable);
    $n = \count($pools);
    $r = $r === null ? $n : $r;
    $product = product(range($n));
    $product->setRepeat($r);
    foreach ($product as $indices) {
        $indices = array_unique(to_array($indices));
        if (\count($indices) == $r) {
            $perm = [];
            foreach ($indices as $i) {
                $perm[] = $pools[$i];
            }
            yield $perm;
        }
    }
}

/**
 * Return r length subsequences of elements from the input iterable.
 */
function combinations($iterable, $r)
{
    $pool = to_array($iterable);
    $n = \count($pool);
    foreach (permutations(range($n), $r) as $indices) {
        $indices = to_array($indices);
        if (sort($indices) === $indices) {
            $res = [];
            foreach ($indices as $i) {
                $res[] = $pool[$i];
            }
            yield $res;
        }
    }
}

/**
 *
 */
function combinations_with_replacement($iterable, $r)
{
    $pool = to_array($iterable);
    $n = \count($pool);
    $product = product(range($n));
    $product->setRepeat($r);
    foreach ($product as $indices) {
        $indices = to_array($indices);
        if (sort($indices) === $indices) {
            $res = [];
            foreach ($indices as $i) {
                $res[] = $pool[$i];
            }
            yield $res;
        }
    }
}
