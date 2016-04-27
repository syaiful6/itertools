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

    /**
     *
     */
    public static function ascending3RandomExchangesInt($length)
    {
        $arr = [];
        for ($i = 0; $i < $length; $i++) {
            $arr[] = $i;
        }
        for ($i = 0; $i < 1; $i++) {
            $first = mt_rand() & ($length - 1);
            $second = mt_rand() & ($length - 1);
            $tmp = $arr[$first];
            $arr[$first] = $arr[$second];
            $arr[$second] = $tmp;
        }
        return $arr;
    }

    public static function allEqualInt($n)
    {
        return array_fill(0, $n, 42);
    }

    /**
     * produce like Orange1, Orange2
     */
    public static function randomStringAscendingInteger($string, $length)
    {
        $arr = [];
        for ($i = 0; $i < $length; $i++) {
            $arr[] = sprintf('%s%d', $string, $length);
        }
        return $arr;
    }
}
