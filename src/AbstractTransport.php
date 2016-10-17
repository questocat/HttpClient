<?php
/**
 * Created by PhpStorm.
 * Author: PhilPu <zhengchaopu@gmail.com>
 * Date: 2016/10/13
 */
namespace HttpClient;

use HttpClient\Uri\Uri;
use HttpClient\Uri\UriInterface;
use HttpClient\Utils\Arr;
use InvalidArgumentException;

/**
 * Class AbstractTransport
 * @package HttpClient
 */
abstract class AbstractTransport implements TransportInterface
{
    /**
     * Contracts
     * GET, DELETE, HEAD 方法, 参数风格为标准的 GET 风格的参数，如 url?a=1&b=2
     * POST, PUT, PATCH, OPTION 方法, 默认情况下请求实体会被视作标准 json 字符串进行处理, 当然, 依旧推荐设置头信息的 Content-Type 为 application/json
     * 在一些特殊接口中（会在文档中说明）, 可能允许 Content-Type 为 application/x-www-form-urlencoded 或者 multipart/form-data, 此时请求实体会被视作标准 POST 风格的参数进行处理
     */

    /**
     * Constants for available HTTP methods
     */
    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';

    const VERSION = '1.0';

    /**
     * @var array
     */
    protected $requestOptions = array();

    /**
     * Make a HTTP GET request.
     * @param $url
     * @param array $query_params
     * @param array $options
     * @return array
     */
    public function get($url, $query_params = array(), array $options = array())
    {
        return $this->request(self::HTTP_GET, $url, $query_params, $options);
    }

    /**
     * Make a HTTP POST request.
     * @param $url
     * @param array $post_params
     * @param array $options
     * @return array
     */
    public function post($url, $post_params = array(), array $options = array())
    {
        return $this->request(self::HTTP_POST, $url, $post_params, $options);
    }

    /**
     * @param $method
     * @param $uri
     * @param $params
     * @return mixed
     */
    protected abstract function retrieveResponse($method, UriInterface $uri, $params);

    /**
     * Send a request to the server and return a Response object with the response.
     * @param string $method
     * @param $url
     * @param array $params
     * @param array $options
     * @return mixed
     */
    public function request($method = self::HTTP_GET, $url, $params = array(), array $options = array())
    {
        // Normalize method name
        $method = strtoupper($method);

        if (!in_array($method, $this->isSupportedHttpRequest())) {
            throw new InvalidArgumentException("The Http [$method] request not available.");
        }

        $this->requestOptions = array_merge_recursive($this->requestOptions, $options);

        // Convert to a Uri object if we were given a string.
        if (is_string($url)) {
            $url = new Uri($url);
        } elseif (!($url instanceof UriInterface)) {
            throw new InvalidArgumentException(
                sprintf('A string or UriInterface object must be provided, a "%s" was provided.', gettype($url))
            );
        }

        return $this->retrieveResponse($method, $url, $params);
    }

    /**
     * Set option value.
     * @param $option
     * @param null $value
     * @return $this
     */
    public function setOpt($option, $value = null)
    {
        foreach ($this->parseOption($option, $value) as $option => $value) {
            $this->requestOptions[$option] = $value;
        }
    }

    /**
     * @param $option
     * @param $value
     * @return array
     */
    protected function parseOption($option, $value)
    {
        return is_array($option) ? $option : array($option => $value);
    }

    /**
     * Unite the HTTP headers.
     * @param array $input
     * @param array $options
     * @return mixed
     */
    public function uniteHeaders(array $input, $options = null)
    {
        $input = $this->getExpectOptions($input, $options);

        array_walk($input, function ($value, $key) use (&$arr) {
            $arr[] = $key ? $key . ':' . $value : $value;
        });

        return $arr;
    }

    /**
     * Split the HTTP headers.
     * @param $rawHeaders
     * @return array
     */
    public function splitHeaders($rawHeaders)
    {
        $headers = [];
        $headerLines = explode("\n", $rawHeaders);
        $headers['HTTP'] = array_shift($headerLines);
        foreach ($headerLines as $line) {
            $header = explode(':', $line, 2);
            if (isset($header[1])) {
                $headers[$header[0]] = trim($header[1]);
            }
        }

        return $headers;
    }

    /**
     * Get expect options for request.
     * @param $default
     * @param $options
     * @return array
     */
    protected function getExpectOptions($default, $options)
    {
        if (isset($options['only'])) {
            return Arr::only($default, $options['only']);
        } elseif (isset($options['except'])) {
            return Arr::except($default, $options['except']);
        }

        return $default;
    }

    /**
     * The Supported for HTTP requests.
     * @return array
     */
    public function isSupportedHttpRequest()
    {
        return array(
            self::HTTP_GET, self::HTTP_POST
        );
    }
}