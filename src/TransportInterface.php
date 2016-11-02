<?php
/**
 * Created by PhpStorm.
 * Author: PhilPu <zhengchaopu@gmail.com>
 * Date: 2016/10/13.
 */
namespace HttpClient;

/**
 * Interface TransportInterface.
 */
interface TransportInterface
{
    /**
     * Send a request to the server and return a Response object with the response.
     *
     * @param string $method
     * @param        $url
     * @param array  $params
     * @param array  $options
     *
     * @return mixed
     */
    public function request($method, $url, $params = array(), $options = array());

    /**
     * Method to check if http transport curl/socket/stream available for use.
     *
     * @return mixed
     */
    public function isSupportedTransport();
}
