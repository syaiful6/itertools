<?php
namespace Itertools;

interface Reverseable
{
    /**
     * Return new \Iterator that can be reprentable as reversed version
     *
     * @return \Iterator
     */
    public function reversed();
}
