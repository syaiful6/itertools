<?php

namespace Itertools\Traits;

use Itertools\StopIteration;

trait SimpleIterable
{
    public function getIterator()
    {
        if (method_exists($this, 'iter')) {
            $iter = $this->iter();
        } else {
            $iter = $this;
        }
        try {
            while (true) {
                yield $iter->next();
            }
        } catch (StopIteration $e) {
            // pass
        }
    }
}
