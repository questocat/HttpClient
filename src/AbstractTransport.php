<?php
/**
 * Created by PhpStorm.
 * Author: PhilPu <zhengchaopu@gmail.com>
 * Date: 2016/10/13.
 */
namespace HttpClient;

use HttpClient\Uri\Uri;
use HttpClient\Uri\UriInterface;
use HttpClient\Utils\Arr;
use InvalidArgumentException;

/**
 * Class AbstractTransport.
 */
abstract class AbstractTransport implements TransportInterface
{
    /**
     * Constants for available HTTP methods.
     */
    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';

    /**
     * version.
     */
    const VERSION = '1.0.0';

    /**
     * Collection request options.
     *
     * @var
     */
    private $options;

    /**
     * Make a HTTP GET request.
     *
     * @param $url
     * @param array $query_params
     * @param array $options
     *
     * @return array
     */
    public function get($url, $query_params = array(), array $options = array())
    {
        return $this->request(self::HTTP_GET, $url, $query_params, $options);
    }

    /**
     * Make a HTTP POST request.
     *
     * @param $url
     * @param array $post_params
     * @param array $options
     *
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
     *
     * @return mixed
     */
    abstract protected function retrieveResponse($method, UriInterface $uri, $params);

    /**
     * Send a request to the server and return a Response object with the response.
     *
     * @param string $method
     * @param $url
     * @param array $params
     * @param array $options
     *
     * @return mixed
     */
    public function request($method, $url, $params = array(), array $options = array())
    {
        // Normalize method name
        $method = strtoupper($method);

        if (!in_array($method, $this->isSupportedHttpRequest(), true)) {
            throw new InvalidArgumentException("The Http [$method] request not available.");
        }

        $this->setOptions(array_merge_recursive($this->options, $options));

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
     * Get the request options.
     *
     * @return Collection
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set option value.
     *
     * @param $option
     * @param null $value
     *
     * @return $this
     */
    public function setOptions($option, $value = null)
    {
        foreach ($this->parseOption($option, $value) as $option => $value) {
            $this->options[$option] = $value;
        }
    }

    /**
     * Parse the option.
     *
     * @param $option
     * @param $value
     *
     * @return array
     */
    protected function parseOption($option, $value)
    {
        return is_array($option) ? $option : array($option => $value);
    }

    /**
     * Unite the HTTP headers.
     *
     * @param array $input
     * @param array $options
     *
     * @return mixed
     */
    public function uniteHeaders(array $input, $options = null)
    {
        $input = $this->getExpectOptions($input, $options);

        array_walk(
            $input,
            function ($value, $key) use (&$arr) {
                $arr[] = $key ? $key.':'.$value : $value;
            }
        );

        return $arr;
    }

    /**
     * Split the HTTP headers.
     *
     * @param $rawHeaders
     *
     * @return array
     */
    public function splitHeaders($rawHeaders)
    {
        $headers = array();
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
     *
     * @param $default
     * @param $options
     *
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
     *
     * @return array
     */
    public function isSupportedHttpRequest()
    {
        return array(
            self::HTTP_GET,
            self::HTTP_POST,
        );
    }

    /**
     * Get default request options.
     *
     * @return array
     */
    protected function getDefaultOptions()
    {
        return array(
            'timeout' => 30,
            'verify' => true,
            'headers' => array(
                'User-Agent' => 'HttpClient/1.0.0',
            ),
        );
    }
}
