<?php
/**
 * Created by PhpStorm.
 * Author: PhilPu <zhengchaopu@gmail.com>
 * Date: 2016/10/13
 */
namespace HttpClient\Exceptions;

use Exception;

/**
 * Class JsonException.
 */
class JsonException extends Exception
{
    /**
     * The json error messages for php5.3 or earlier.
     *
     * @var array
     */
    public static $messages = array(
        JSON_ERROR_NONE => 'No error has occurred',
        JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
        JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
        JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
        JSON_ERROR_SYNTAX => 'Syntax error',
        JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
        JSON_ERROR_RECURSION => 'The object or array passed to json_encode() include recursive references and cannot be encoded',
        JSON_ERROR_INF_OR_NAN => 'The value passed to json_encode() includes either NAN or INF',
        JSON_ERROR_UNSUPPORTED_TYPE => 'A value of an unsupported type was given to json_encode(), such as a resource',
    );

    /*
     * JsonException constructor.
     */
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        $error_msg = array_key_exists($code, self::$messages)
            ? self::$messages[$code]
            : 'Unknown Error';

        $message = !empty($message) ? $message." #{$code}: ".$error_msg : $error_msg;

        parent::__construct($message, $code, $previous);
    }
}