<?php
/**
 * Created by PhpStorm.
 * Author: PhilPu <zhengchaopu@gmail.com>
 * Date: 2016/10/5.
 */
namespace HttpClient\Transports;

use HttpClient\AbstractTransport;
use HttpClient\Exceptions\ResponseException;
use HttpClient\Uri\UriInterface;
use RuntimeException;

/**
 * Class StreamTransport.
 */
class StreamTransport extends AbstractTransport
{
    /**
     * StreamTransport constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        if (!$this->isSupportedTransport()) {
            throw new RuntimeException('Cannot use a stream transport when file_get_contents() is not available.');
        }

        $this->registerOptions($config);
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
            'max_redirects' => 5,
            'headers' => array(
                'User-Agent' => 'StreamTransport/1.0.0',
            ),
        );
    }

    /**
     * Send a request to the server and return a Response object with the response.
     *
     * @param $method
     * @param $uri
     * @param $params
     *
     * @return bool|string
     *
     * @throws ResponseException
     */
    protected function retrieveResponse($method, UriInterface $uri, $params)
    {
        $options = $this->getOptions();
        $params = is_array($params) ? http_build_query($params, '', '&') : $params;

        // Add the relevant headers.
        $options['headers']['Content-Length'] = strlen($params);
        $headers = $this->uniteHeaders($options['headers']);

        $context = $this->createStreamContext($method, $headers, $params, $options);

        $level = error_reporting(0);
        $response = @file_get_contents($uri->getAbsoluteUri(), false, $context);
        error_reporting($level);

        if (false === $response) {
            $lastError = error_get_last();
            if (is_null($lastError)) {
                throw new ResponseException(
                    'Failed to request resource. HTTP Code: '.
                    (isset($http_response_header[0]) ? $http_response_header[0] : 'No response')
                );
            }

            throw new ResponseException($lastError['message']);
        }

        return $response;
    }

    /**
     * Create the stream context for the request.
     *
     * @param $method
     * @param $headers
     * @param $body
     * @param $options
     *
     * @return resource
     */
    private function createStreamContext($method, $headers, $body, $options)
    {
        return stream_context_create(
            array(
                'http' => array(
                    'method' => $method,
                    'header' => implode("\r\n", array_values($headers)),
                    'content' => $body,
                    'protocol_version' => '1.1',
                    'user_agent' => $options['headers']['User-Agent'],
                    'max_redirects' => $options['max_redirects'],
                    'timeout' => $options['timeout'],
                ),
            )
        );
    }

    /**
     * Method to check if http transport stream available for use.
     *
     * @return bool
     */
    public function isSupportedTransport()
    {
        return function_exists('file_get_contents') && is_callable('file_get_contents');
    }
}
