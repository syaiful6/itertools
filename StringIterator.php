<?php
namespace Itertools;

use Iterator;
use Itertools\Traits\KeyPosition;

class StringIterator implements Iterator
{
    use KeyPosition;
    /**
     * @var string the underlying string
     */
	private $string;

    /**
     * @var encoding
     */
	private $encoding;

    /**
     * Create new StringIterator
     *
     * @param string $string to iterate
     * @param string $encoding for the string, if not given we use mb string
     * @return void
     */
    public function __construct($string, $encoding = null)
    {
        $this->string = (string) $string;
        if (!$encoding) {
            $encoding = mb_detect_encoding($this->string)
        }
        $this->encoding = $encoding;
    }

    /**
     * Return the current element
     *
     * @link http://php.net/manual/en/iterator.current.php
     * @return string
     */
    public function current()
    {
        return mb_substr($this->string, $this->position, 1, $this->encoding);
    }

    /**
     * Checks if current position is valid
     *
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean
     */
    public function valid()
    {
        return $this->position < mb_strlen($this->string, $this->encoding);
    }
}
