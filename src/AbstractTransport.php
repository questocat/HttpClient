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
     * Collection the options.
     *
     * @var
     */
    protected $options;

    /**
     * The Supported for HTTP requests.
     *
     * @var array
     */
    protected $supportedHttpRequest = array('GET', 'POST');

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
    public function request($method, $url, $params = array(), $options = array())
    {
        $method = strtoupper($method);

        if (!$this->isSupportedHttpRequest($method)) {
            throw new InvalidArgumentException("The Http [$method] request not available.");
        }

        if (!is_array($options)) {
            throw new InvalidArgumentException('The optional options must be arrays.');
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
     * Magic methods for http request.
     *
     * @param $method
     * @param $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (count($args) < 1) {
            throw new InvalidArgumentException(
                'Magic request methods require a URI and optional options array'
            );
        }

        $uri = $args[0];
        $params = isset($args[1]) ? $args[1] : array();
        $opts = isset($args[2]) ? $args[2] : array();

        return $this->request($method, $uri, $params, $opts);
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
     * Normalize the HTTP headers.
     *
     * @param array $input
     * @param array $options
     *
     * @return mixed
     */
    public function normalizeHeaders(array $input, $options = null)
    {
        $input = $this->getExpectOptions($input, $options);

        $headers = array();

        array_walk(
            $input,
            function ($value, $key) use (&$headers) {
                $headers[] = $key ? $key.':'.$value : $value;
            }
        );

        return $headers;
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
     * Get the default options.
     *
     * @return array
     */
    protected function getDefaultOptions()
    {
        return array();
    }

    /**
     * Method to check that this is a supported HTTP request.
     *
     * @param $method
     *
     * @return bool
     */
    protected function isSupportedHttpRequest($method)
    {
        return in_array($method, $this->supportedHttpRequest, true);
    }
}
