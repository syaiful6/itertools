<?php
namespace Itertools\Traits;

trait KeyPosition
{
	protected $position = 0;

    /**
     * Rewind the Iterator to the first element
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Return the key of the current element
     *
     * @link http://php.net/manual/en/iterator.key.php
     * @return scalar
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Move forward to next element
     *
     * @link http://php.net/manual/en/iterator.next.php
     * @return void
     */
    public function next()
    {
        $this->position++;
    }
}