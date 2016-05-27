<?php

namespace Itertools;

/**
 * This is a workaround for the problem with \FilterIterator leaving inner
 * \FilesystemIterator in wrong state after rewind in some cases.
 */
abstract class FilterIterator extends \FilterIterator
{
    /**
     *
     * @see FilterIterator::rewind()
     */
    public function rewind()
    {
        if (PHP_VERSION_ID > 50607 || (PHP_VERSION_ID > 50523 && PHP_VERSION_ID < 50600)) {
            parent::rewind();

            return;
        }

        $iterator = $this;
        while ($iterator instanceof \OuterIterator) {
            $innerIterator = $iterator->getInnerIterator();

            if ($innerIterator instanceof RecursiveDirectoryIterator) {
                if ($innerIterator->isRewindable()) {
                    $innerIterator->next();
                    $innerIterator->rewind();
                }
            } elseif ($innerIterator instanceof \FilesystemIterator) {
                $innerIterator->next();
                $innerIterator->rewind();
            }

            $iterator = $innerIterator;
        }

        parent::rewind();
    }
}
