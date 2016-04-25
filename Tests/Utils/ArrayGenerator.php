<?php
namespace Itertools\Tests\Utils;

class ArrayGenerator
{
    /**
     *
     */
    public static function randomInt($length)
    {
        $arr = [];
        for ($i = 0; $i < $length; $i++) {
            $arr[] = mt_rand();
        }

        return $arr;
    }

    /**
     *
     */
    public static function descendingInt($length)
    {
        $arr = [];
        for ($i = 0; $i < $length; $i++) {
            $arr[] = $length - $i;
        }

        return $arr;
    }

    /**
     *
     */
    public static function ascendingInt($length)
    {
        $arr = [];
        for ($i = 0; $i < $length; $i++) {
            $arr[] = $i;
        }

        return $arr;
    }

    public static function allEqualInt($n)
    {
        return array_fill(0, $n, 42);
    }
}
