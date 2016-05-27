<?php

namespace Itertools;

use IteratorAggregate;
use Itertools\Traits\SimpleIterable;

class Groupby implements IteratorAggregate
{
    use SimpleIterable;
    /**
     * @var callable
     */
    protected $grouper;

    /**
     * @var iterable to group
     */
    protected $iterable;

    /**
     *
     */
    protected $tgtkey;

    /**
     *
     */
    protected $currkey;

    /**
     *
     */
    protected $currvalue;

    /**
     *
     */
    public function __construct($grouper, $iterable)
    {
        // if no grouper passed, set them to closure that do nothing
        if ($grouper === null) {
            $grouper = function ($x) { return $x; };
        }
        $this->iterable = iter($iterable);
        $this->grouper = $grouper;
    }

    /**
     *
     */
    public function next()
    {
        $grouper = $this->grouper;
        while ($this->currkey === $this->tgtkey) {
            $this->currvalue = next($this->iterable);
            $this->currkey = $grouper($this->currvalue);
        }
        $this->tgtkey = $this->currkey;

        return [$this->currkey, $this->group($this->tgtkey)];
    }

    /**
     * The StopIteration here must be catches
     */
    private function group($tgtkey)
    {
        $grouper = $this->grouper;
        try {
            while ($this->currkey === $tgtkey) {
                yield $this->currvalue;
                $this->currvalue = next($this->iterable);
                $this->currkey = $grouper($this->currvalue);
            }
        } catch (StopIteration $e) {
            return;
        }
    }
}
