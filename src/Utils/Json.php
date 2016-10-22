<?php
/**
 * Created by PhpStorm.
 * Author: PhilPu <zhengchaopu@gmail.com>
 * Date: 2016/10/7.
 */
namespace HttpClient\Utils;

use HttpClient\Exceptions\JsonException;

// for PHP5.3, prevent PHP Notice msg assumed
defined('JSON_UNESCAPED_UNICODE') || define('JSON_UNESCAPED_UNICODE', 256);

/**
 * Class Json.
 */
class Json
{
    /**
     * Returns array for JSON string.
     */
    const DECODE_ASSOC = true;

    /**
     * Returns object for JSON string.
     */
    const DECODE_OBJECT = false;

    /*
     * Returns the JSON representation of a value
     * Test By php 5.3、php5.6
     * 使用无效的 UTF8 序列测试
     * $data = "\xB1\x31";
     *
     * @param mixed $data 	The data being encoded. Can be any type except a resource. Only works with UTF-8 encoded data
     * @param int $options	Bitmask of json_encode options
     * @param int $depth    Maximum depth
     *
     * @return string Returns a JSON encoded string on success
     *
     * @throws JsonException
     */
    public static function encode($data, $options = 0, $depth = 512)
    {
        if (version_compare(PHP_VERSION, '5.5.0', '>=')) {
            $json_data = @json_encode($data, $options | JSON_UNESCAPED_UNICODE, $depth);
        } elseif (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $json_data = @json_encode($data, $options | JSON_UNESCAPED_UNICODE);
        } else {
            $data = version_compare(PHP_VERSION, '5.3.0', '>=') ? @json_encode($data, $options) : @json_encode($data);
            $json_data = preg_replace_callback(
                '/\\\\u([0-9a-f]{2})([0-9a-f]{2})/iu',
                create_function(
                    '$pipe',
                    'return iconv(
                        strncasecmp(PHP_OS, "WIN", 3) ? "UCS-2BE" : "UCS-2",
                        "UTF-8",
                        chr(hexdec($pipe[1])) . chr(hexdec($pipe[2]))
                    );'
                ),
                $data
            );
        }

        $json_error = json_last_error();

        if ($json_data === false || $json_error !== JSON_ERROR_NONE) {
            throw new JsonException('Error encoding JSON', $json_error);
        }

        return $json_data;
    }

    /*
     * Decodes a JSON string
     * Test By php 5.3、php5.6
     * 使用无效的 json 字符串测试
     * $data = "{'Organization': 'PHP Documentation Team'}";
     *
     * @param mixed $data 		The json string being decoded. Only works with UTF-8 encoded data
     * @param bool $assoc		When TRUE, returned objects will be converted into associative arrays
     * @param int $depth		User specified recursion depth
     * @param array $options    Array of option flags
     *
     * @return string Returns the contents of the JSON encoded string as the appropriate PHP type on success
     *
     * @throws JsonException
     */
    public static function decode($data, $assoc = self::DECODE_OBJECT, $depth = 512, $options = 0)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $decoded_data = @json_decode($data, $assoc, $depth, $options);
        } elseif (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            $decoded_data = @json_decode($data, $assoc, $depth);
        } else {
            $decoded_data = @json_decode($data, $assoc);
        }

        $json_error = json_last_error();

        if (is_null($decoded_data) || $json_error !== JSON_ERROR_NONE) {
            throw new JsonException('Error decoding JSON', $json_error);
        }

        return $decoded_data;
    }

}
