<?php
namespace Itertools;

use Iterator;
use Itertools\Traits\KeyPosition;

/**
 *  An infinite iterator that returns evenly spaced values starting with number
 *  start and move with number of step.
 */
class CountIterator implements Iterator
{
    use KeyPosition;

    protected $start;

    protected $step;

    protected $started = false;

    /**
     *
     */
    public function __construct($start, $step = 1)
    {
        $this->start = $start;
        if ($step === 0) {
            throw new \InvalidArgumentException(
                'Argument 2 passed to CountIterator must not zero'
            );
        }
        $this->step = $step;
    }

    /**
     * Return the current item
     *
     * @return number
     */
    public function current()
    {
        if (! $this->started) {
            $this->started = true;

            return $this->start;
        }

        return $this->start + ($this->step * $this->position);
    }

    /**
     * This iterator is infinite so always return true
     *
     * @return boolean always true
     */
    public function valid()
    {
        return true;
    }

    public function __debugInfo()
    {
        return sprintf('CountIterator(%d, %d)', $this->start, $this->step);
    }
}
