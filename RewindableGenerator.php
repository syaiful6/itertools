<?php

namespace Itertools;

use Iterator;
use BadMethodCallException;

class RewindableGenerator implements Iterator
{
    protected $genfunc;

    protected $args;

    protected $generator;

    /**
     *
     */
    public function __construct(callable $genfunc, ...$args)
    {
        $this->genfunc = $genfunc;
        $this->args = $args;
    }

    /**
     *
     */
    public function rewind()
    {
        $gen = $this->genfunc;
        $this->generator = $gen(...$this->args);
    }

    /**
     *
     */
    public function key()
    {
        $this->rewindIfGeneratorNull();

        return $this->generator->key();
    }

    /**
     *
     */
    public function next()
    {
        $this->rewindIfGeneratorNull();
        $this->generator->next();
    }

    /**
     *
     */
    public function valid()
    {
        $this->rewindIfGeneratorNull();

        return $this->generator->valid();
    }

    /**
     *
     */
    public function current()
    {
        $this->rewindIfGeneratorNull();

        return $this->generator->current();
    }

    public function send($value = null)
    {
        $this->rewindIfGeneratorNull();

        return $this->generator->send($value);
    }

    /**
     *
     */
    private function rewindIfGeneratorNull()
    {
        if (!$this->generator) {
            $this->rewind();
        }
    }

    /**
     *
     */
    public function __call($method, $args)
    {
        if ($method === 'throw') {
            $this->rewindIfGeneratorNull();

            return $this->generator->throw(...$args);
        }
        throw new BadMethodCallException("Method {$method} does not exist.");
    }
}
