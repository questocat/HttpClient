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
     * Collection the options.
     *
     * @var
     */
    private $options;

    /**
     * Make a HTTP GET request.
     *
     * @param       $url
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
     * @param       $url
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
     * Retrieve the http response.
     *
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
     * @param        $url
     * @param array  $params
     * @param array  $options
     *
     * @return mixed
     */
    public function request($method, $url, $params = array(), array $options = array())
    {
        $method = strtoupper($method);

        if (!in_array($method, $this->getSupportedHttpRequest(), true)) {
            throw new InvalidArgumentException("The Http [$method] request not available.");
        }

        $this->mergeOptions($options);

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
     * Get a transport option.
     *
     * @param $option
     *
     * @return mixed
     */
    public function getOption($option)
    {
        return $this->options->get($option);
    }

    /**
     * Get all transport options.
     *
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options->all();
    }

    /**
     * Merge the collection with the given options.
     *
     * @param $option
     *
     * @return mixed
     */
    public function mergeOptions($option)
    {
        return $this->options->merge($option);
    }

    /**
     * Set option value.
     *
     * @param      $option
     * @param null $value
     *
     * @return $this
     */
    public function setOptions($option, $value = null)
    {
        foreach ($this->parseOption($option, $value) as $option => $value) {
            $this->options->set($option, $value);
        }

        return $this;
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
     * The Supported for HTTP requests.
     *
     * @return array
     */
    public function getSupportedHttpRequest()
    {
        return array(
            self::HTTP_GET,
            self::HTTP_POST,
        );
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
     * Register the options.
     *
     * @param array $options
     */
    protected function registerOptions(array $options)
    {
        $defaults = $this->getDefaultOptions();
        $options = array_merge($defaults, $options);
        $this->configureOptions($options);
    }

    /**
     * Configure the options.
     *
     * @param array $options
     */
    protected function configureOptions(array $options)
    {
        if (is_array($options)) {
            $this->options = new Collection($options);
        } else {
            if ($options instanceof Collection) {
                $this->options = clone $options;
            } else {
                throw new InvalidArgumentException('Expected array or Collection.');
            }
        }
    }

    /**
     * Get default the options.
     *
     * @return array
     */
    protected function getDefaultOptions()
    {
        return array();
    }
}
