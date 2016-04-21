<?php
namespace Itertools;

use Iterator;

class StreamIterator implements Iterator
{
    /**
     * @var resourse
     */
	protected $stream;

    /**
     *
     */
    public function __construct($stream)
    {
        $this->stream = $stream;
        $this->assertStream();
    }

    /**
     * Checks if current position is valid
     *
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean
     */
    public function valid()
    {
        if (! is_resource($this->stream)) {
            return false;
        }
        return !feof($this->stream);
    }

    /**
     * Return the current element
     *
     * @link http://php.net/manual/en/iterator.current.php
     * @return string
     */
    public function current()
    {
        return fgets($this->stream);
    }

    /**
     *
     */
    public function rewind()
    {
        if (! $this->stream) {
            return;
        }
        $meta = stream_get_meta_data($this->stream);
        if ($meta['seekable']) {
            fseek($this->stream, 0, SEEK_SET);
        }
    }

    /**
     * Move, no operation needed
     */
    public function next()
    {
    }

    /**
     *
     */
    public function key()
    {
        if (! is_resource($this->stream)) {
            throw new \RuntimeException(
                'No resource available; cannot tell position'
            );
        }
        $result = ftell($this->stream);

        if (! is_int($result)) {
            throw new RuntimeException('Error occurred during tell operation');
        }
        return $result;
    }

    /**
     * assert the underlying stream variable is resource and it opened in read mode
     */
    protected function assertStream()
    {
        if (! is_resource($stream)) {
            throw new \InvalidArgumentException(
                'non resource value given'
            );
        }
        $meta = stream_get_meta_data($this->stream);
        $mode = $meta['mode'];
        if (!strstr($mode, 'r') || !strstr($mode, '+')) {
            throw new \InvalidArgumentException(
                'cant iterate over non readable resource.'
            );
        }
    }
}
