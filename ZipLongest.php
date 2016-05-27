<?php

namespace Itertools;

use Iterator;
use Itertools\Traits\KeyPosition;
/**
 * Like zip but this one continue iteration until all iterator exhausted.
 * This iterator is forward iterator and prevent rewindable call to all iterator
 * passed here because we dont know all iterator passed here is rewindable.
 * But the benefit are:
 * - any one can start one or more iterator before passed here.
 * - consistency with zip
 */
class ZipLongest implements Iterator
{
    use KeyPosition;

    protected $fillValue;

    protected $iterators;

    /**
     * create new ZipLongest object
     *
     * @param  \Iterators[] $iterator
     * @return void
     */
    public function __construct(Iterator ...$iterators)
    {
        $this->iterators = $iterators;
    }

    /**
    * Set fill value for iterator that already exhausted
    *
    * @param mixed fillValue
    * @return mixed
    */
    public function setFillValue($fillValue)
    {
        $this->fillValue = $fillValue;
    }

    /**
     * Get current fillValue
     */
    public function getFillValue()
    {
        return $this->fillValue;
    }

    /**
     * Check if the current position is valid
     *
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean
     */
    public function valid()
    {
        foreach ($this->iterators as $it) {
            if ($it->valid()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the current element
     *
     * @return mixed
     */
    public function current()
    {
        $res = [];
        foreach ($this->iterators as $it) {
            if ($it->valid()) {
                $res[] = $it->current();
            } else {
                $res[] = $this->fillValue;
            }
        }

        return $res;
    }

    /**
     *
     */
    public function next()
    {
        foreach ($this->iterators as $it) {
            $it->next();
        }
    }
}
