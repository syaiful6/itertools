<?php
namespace Itertools\Traits;

use Itertools\RewindableGenerator;
use Itertools\StopIteration;

trait SimpleIterable
{
    public function getIterator()
    {
        return new RewindableGenerator([$this, '_innerIterator']);
    }

    public function _innerIterator()
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
