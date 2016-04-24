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

    public function __construct(Iterator ...$iterators)
    {
        $this->iterators = $iterators;
    }

    /**
    *
    */
    public function setFillValue($fillValue)
    {
        $this->fillValue = $fillValue;
    }

    /**
     *
     */
    public function getFillValue()
    {
        return $this->fillValue;
    }

    /**
     *
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
     *
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
