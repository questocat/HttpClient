<?php
/**
 * Created by PhpStorm.
 * Author: PhilPu <zhengchaopu@gmail.com>
 * Date: 2016/10/16.
 */
use \HttpClient\Utils\Arr;

if (!function_exists('array_only')) {
    /**
     * Get a subset of the items from the given array.
     *
     * @param $array
     * @param $keys
     *
     * @return array
     */
    function array_only($array, $keys)
    {
        return Arr::only($array, $keys);
    }
}

if (!function_exists('getallheaders')) {
    /**
     * Get all HTTP request header information.
     *
     * @return mixed
     */
    function getallheaders()
    {
        array_walk(
            $_SERVER,
            function ($value, $name) use (&$headers) {
                if (substr($name, 0, 5) === 'HTTP_') {
                    $headers[str_replace(
                        ' ',
                        '-',
                        ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))
                    )] = $value;
                }
            }
        );

        return $headers;
    }
}