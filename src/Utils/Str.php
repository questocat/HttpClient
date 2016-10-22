<?php
/**
 * Created by PhpStorm.
 * Author: PhilPu <zhengchaopu@gmail.com>
 * Date: 2016/10/7
 */
namespace HttpClient\Utils;

/**
 * Class Str.
 */
class Str
{
    /**
     * Convert a value to studly caps case.
     *
     * @param $str
     *
     * @return mixed
     */
    public static function studly($str)
    {
        $str = ucwords(str_replace(array('-', '_'), ' ', strtolower($str)));

        return str_replace(' ', '', $str);
    }
}