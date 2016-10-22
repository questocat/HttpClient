<?php
/**
 * Created by PhpStorm.
 * Author: PhilPu <zhengchaopu@gmail.com>
 * Date: 2016/10/9.
 */
namespace HttpClient\Utils;

/**
 * Class Arr.
 */
class Arr
{
    /**
     * 从数组返回给定的键值对.
     *
     * @param $array
     * @param $keys
     *
     * @return array
     */
    public static function only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * 从数组返回给定的键值对以外的键值对.
     *
     * @param $array
     * @param $keys
     *
     * @return array
     */
    public static function except($array, $keys)
    {
        return array_diff_key($array, array_flip((array) $keys));
    }

}