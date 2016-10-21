<?php
/**
 * Created by PhpStorm.
 * Author: PhilPu <zhengchaopu@gmail.com>
 * Date: 2016/10/7
 */
namespace HttpClient\Transports;

use HttpClient\AbstractTransport;
use HttpClient\Exceptions\ResponseException;
use HttpClient\Uri\Uri;
use HttpClient\Uri\UriInterface;
use RuntimeException;
use UnexpectedValueException;

/**
 * Class SocketTransport
 *
 * @package HttpClient\Transports
 */
class SocketTransport extends AbstractTransport
{
    /**
     * Reusable socket handles.
     *
     * @var
     */
    protected $handles;

    /**
     * SocketTransport constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        if (!$this->isSupportedTransport()) {
            throw new RuntimeException('Cannot use a socket transport when fsockopen() is not available.');
        }

        $this->registerOptions($config);
    }

    /**
     * Register request options
     *
     * @param array $options
     */
    protected function registerOptions(array $options)
    {
        $defaults = array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'SocketTransport/1.0.1',
            ),
        );

        $options = array_merge_recursive($defaults, $options);
        $this->setOptions($options);
    }

    /**
     * Send a request to the server and return a Response object with the response.
     *
     * @param $method
     * @param $uri
     * @param $params
     *
     * @return Response|mixed|string
     * @throws InvalidResponseCodeException
     */
    protected function retrieveResponse($method, UriInterface $uri, $params)
    {
        $options = $this->getOptions();
        $fp = $this->connect($uri, $options['timeout']);
        if (is_resource($fp)) {
            $meta = stream_get_meta_data($fp);
            if ($meta['timed_out']) {
                throw new RuntimeException('Server connection timed out.');
            }
        } else {
            throw new RuntimeException('Not connected to server.');
        }

        $data = is_array($params) ? http_build_query($params) : $params;

        $path = $uri->getMultiParts(array('path', 'query'));
        $request_method = $method.' '.((empty($path)) ? '/' : $path);
        if (!empty($data)) {
            $request_method .= '?'.$data;
        }
        $request_method .= ' HTTP/1.0';
        $headers[] = $request_method;
        $headers[] = 'Host:'.$uri->getHost();

        if (!isset($options['headers']['Authorization']) && $userInfo = $uri->getUserInfo()) {
            $options['headers']['Authorization'] = 'Basic '.base64_encode($userInfo);
        }

        $headers = array_merge_recursive($headers, $this->uniteHeaders($options['headers']));

        fputs($fp, implode("\r\n", $headers)."\r\n\r\n");

        $content = '';
        while (!feof($fp)) {
            $content .= @fgets($fp, 4096);
        }

        fclose($fp);

        $response = $this->getResponse($content);

        // Follow Http redirects
        if ($response->code >= 301 && $response->code < 400 && isset($response->headers['Location'])) {
            return $this->retrieveResponse($method, new Uri($response->headers['Location']), $data);
        }

        return $content;
    }

    /**
     * Method to get a response object from a server response.
     *
     * @param $content
     *
     * @return Response
     * @throws ResponseException
     */
    protected function getResponse($content)
    {
        if (empty($content)) {
            throw new UnexpectedValueException('No content in response.');
        }

        $result = new \stdClass();
        $response = explode("\r\n\r\n", $content, 2);
        $headers = explode("\r\n", $response[0]);
        $result->body = empty($response[1]) ? '' : $response[1];
        preg_match('/[0-9]{3}/', array_shift($headers), $matches);
        $code = $matches[0];
        if (is_numeric($code)) {
            $result->code = (int)$code;
        } else {
            throw new ResponseException('No HTTP response code found.');
        }

        foreach ($headers as $header) {
            $pos = strpos($header, ':');
            $result->headers[trim(substr($header, 0, $pos))] = trim(substr($header, ($pos + 1)));
        }

        return $result;
    }

    /**
     * Method to connect to a server and get the resource.
     *
     * @param UriInterface $uri
     * @param null $timeout
     *
     * @return mixed
     */
    protected function connect(UriInterface $uri, $timeout = null)
    {
        $host = $uri->isSsl() ? 'ssl://'.$uri->getHost() : $uri->getHost();
        $port = $uri->getPort();
        $key = md5($host.$port);

        if (!empty($this->handles[$key]) && is_resource($this->handles[$key])) {
            $meta = stream_get_meta_data($this->handles[$key]);
            if ($meta['eof']) {
                if (!fclose($this->handles[$key])) {
                    throw new RuntimeException('Cannot close connection');
                }
            } elseif (!$meta['timed_out']) {
                return $this->handles[$key];
            }
        }

        if (!is_numeric($timeout)) {
            $timeout = ini_get('default_socket_timeout');
        }

        $track_errors = ini_get('track_errors');
        ini_set('track_errors', true);

        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$fp) {
            if (!$errstr) {
                $errstr = sprintf('Could not connect to resource: %s #%s %s', $uri, $errno, $errstr);
            }
            ini_set('track_errors', $track_errors);
            throw new RuntimeException($errstr);
        }

        ini_set('track_errors', $track_errors);
        $this->handles[$key] = $fp;

        if (isset($timeout)) {
            stream_set_timeout($this->handles[$key], (int)$timeout);
        }

        return $this->handles[$key];
    }

    /**
     * Method to check if http transport socket available for use.
     *
     * @return bool
     */
    public function isSupportedTransport()
    {
        return function_exists('fsockopen') && is_callable('fsockopen');
    }
}