<?php
/**
 * Created by PhpStorm.
 * Author: PhilPu <zhengchaopu@gmail.com>
 * Date: 2016/10/13.
 */
namespace HttpClient\Transports;

use CURLFile;
use Exception;
use HttpClient\AbstractTransport;
use HttpClient\Exceptions\ResponseException;
use HttpClient\Uri\UriInterface;
use HttpClient\Utils\Json;
use HttpClient\Utils\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class CurlTransport.
 */
class CurlTransport extends AbstractTransport
{
    /**
     * The cURL handle.
     *
     * @var null|resource
     */
    protected $ch = null;

    /**
     * CurlTransport constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        if (!$this->isSupportedTransport()) {
            throw new RuntimeException('Cannot use a cURL transport when curl_version() is not available.');
        }

        $this->ch = curl_init();
        $this->registerOptions($config);
    }

    /**
     * Clean up the cURL handle.
     */
    public function __destruct()
    {
        if (is_resource($this->ch)) {
            curl_close($this->ch);
        }
    }

    /**
     * Get default request options.
     *
     * @return array
     */
    protected function getDefaultOptions()
    {
        return array(
            'media' => 'json',
            'timeout' => 30,
            'verify' => true,
            'headers' => array(
                'User-Agent' => 'CurlTransport/1.0.0',
            ),
        );
    }

    /**
     * @param $url
     * @param $method
     * @param $params
     *
     * @return string
     */
    protected function prepareForUrl($url, $method, &$params)
    {
        if (in_array($method, array(self::HTTP_GET, self::HTTP_HEAD, self::HTTP_DELETE), true)) {
            if (!empty($params) && is_array($params)) {
                $url .= (strpos($url, '?') ? '&' : '?').http_build_query($params);
                $params = array();
            }
        }

        return $url;
    }

    /**
     * Build HTTP request params.
     *
     * @param $params
     *
     * @return mixed|string
     */
    protected function prepareBuildParams($params)
    {
        if (!empty($params) && is_array($params)) {
            $media = $this->getOption('media');
            if ($media === 'json') {
                $params = Json::encode($params);
            } elseif ($media === 'urlencoded') {
                $params = http_build_query($params);
            }
        }

        return $params;
    }

    /**
     * Send a request to the server and return a Response object with the response.
     *
     * @param $method
     * @param $uri
     * @param $params
     *
     * @return array
     *
     * @throws ResponseException
     */
    protected function retrieveResponse($method, UriInterface $uri, $params)
    {
        $options = $this->getOptions();

        $url = $this->prepareForUrl($uri->getAbsoluteUri(), $method, $params);
        $params = $this->prepareBuildParams($params);

        $optionConf = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => true,  // 启用时会将头文件的信息作为数据流输出
            CURLOPT_RETURNTRANSFER => true,  // 将curl_exec()获取的信息以文件流的形式返回，而不是直接输出
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => $options['timeout'],
        );

        $this->setOptionConf($method, $params, $optionConf);

        if (false === $options['verify']) {
            $optionConf[CURLOPT_SSL_VERIFYPEER] = false;
            $optionConf[CURLOPT_SSL_VERIFYHOST] = 0;
        } else {
            $optionConf[CURLOPT_SSL_VERIFYPEER] = true;
            $optionConf[CURLOPT_SSL_VERIFYHOST] = 2;
            if (is_string($options['verify'])) {
                if (!file_exists($options['verify'])) {
                    throw new InvalidArgumentException(
                        "SSL CA bundle not found: {$options['verify']}"
                    );
                }
                $optionConf[CURLOPT_CAINFO] = $options['verify'];
            }
        }

        // 使用证书情况
        if (isset($options['ssl_cert_path']) && isset($options['ssl_key_path'])) {
            $sslCert = $options['ssl_cert_path'];
            if (!file_exists($sslCert)) {
                throw new InvalidArgumentException(
                    "SSL certificate not found: {$sslCert}"
                );
            }
            $sslKey = $options['ssl_key_path'];
            if (!file_exists($sslKey)) {
                throw new InvalidArgumentException(
                    "SSL private key not found: {$sslKey}"
                );
            }
            // 设置证书
            // 使用证书：cert 与 key 分别属于两个.pem文件
            $optionConf[CURLOPT_SSLCERTTYPE] = 'PEM';
            $optionConf[CURLOPT_SSLCERT] = $options['ssl_cert_path'];
            $optionConf[CURLOPT_SSLKEYTYPE] = 'PEM';
            $optionConf[CURLOPT_SSLKEY] = $options['ssl_key_path'];
        }

        if ($options['media'] === 'json') {
            $options['headers']['Content-Type'] = 'application/json';
        }

        $headers = $this->normalizeHeaders($options['headers']);
        $optionConf[CURLOPT_HTTPHEADER] = $headers;

        // Check for basic auth.
        if (isset($options['auth']['type']) && 'basic' === $options['auth']['type']) {
            $optionConf[CURLOPT_USERPWD] = $options['auth']['username'].':'.$options['auth']['password'];
        }

        curl_setopt_array($this->ch, $optionConf);
        $results = $this->execCurl();

        // Separate headers and body.
        $headerSize = $results['curl_info']['header_size'];
        $header = trim(substr($results['response'], 0, $headerSize));
        $body = trim(substr($results['response'], $headerSize));

        // Assemble the result.
        return array(
            'status' => $results['curl_info']['http_code'],
            'headers' => $this->splitHeaders($header),
            'curl_info' => $results['curl_info'],
            'data' => $body,
        );
    }

    /**
     * Setting cURL option for HTTP request.
     *
     * @param       $method
     * @param       $params
     * @param array $optionConf
     */
    protected function setOptionConf($method, $params, array &$optionConf)
    {
        // Specify settings according to the HTTP method
        $method = 'set'.Str::studly($method).'OptionConf';

        if (method_exists($this, $method)) {
            $this->{$method}($params, $optionConf);
        }
    }

    /**
     * Setting cURL option for HTTP POST request.
     *
     * @param $params
     * @param $optionConf
     *
     * @throws Exception
     */
    protected function setPostOptionConf($params, array &$optionConf)
    {
        $files = $this->getOption('files');

        if (!empty($files)) {
            if (!is_array($files) || !count($files)) {
                throw new Exception("Files must be an array and can't be empty");
            }
            unset($params);
            foreach ($files as $index => $file) {
                $params[$index] = $this->createCurlFile($file);
            }
        }

        $optionConf[CURLOPT_POST] = true;
        $optionConf[CURLOPT_POSTFIELDS] = $params;
    }

    /**
     * Setting cURL option for HTTP PUT request.
     *
     * @param $params
     * @param $optionConf
     */
    protected function setPutOptionConf($params, array &$optionConf)
    {
        $filename = $this->getOption('files');

        if (!empty($filename)) {
            $fp = fopen($filename, 'r');
            $optionConf[CURLOPT_PUT] = true;
            $optionConf[CURLOPT_INFILE] = $fp;
            $optionConf[CURLOPT_INFILESIZE] = filesize($filename);
        } else {
            $optionConf[CURLOPT_POST] = true;
            $optionConf[CURLOPT_POSTFIELDS] = $params;
        }
    }

    /**
     * Setting cURL option for HTTP PATCH request.
     *
     * @param $params
     * @param $optionConf
     */
    protected function setPatchOptionConf($params, array &$optionConf)
    {
        $optionConf[CURLOPT_POST] = true;
        $optionConf[CURLOPT_POSTFIELDS] = $params;
    }

    /**
     * Make curl file.
     * http://php.net/manual/zh/class.curlfile.php.
     *
     * @param $filename
     *
     * @return \CURLFile|string
     */
    protected function createCurlFile($filename)
    {
        if (class_exists('\CURLFile')) {
            return new CURLFile($filename);
        }

        return "@$filename;filename=".basename($filename);
    }

    /**
     * Do execute cURL.
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function execCurl()
    {
        $response = curl_exec($this->ch);
        $curlInfo = curl_getinfo($this->ch);
        $responseCode = $curlInfo['http_code'];

        if (false === $response || $responseCode !== 200) {
            $err_no = curl_errno($this->ch);
            $error = curl_error($this->ch);

            if (empty($error)) {
                throw new ResponseException('Failed to request resource.', $responseCode);
            }

            throw new ResponseException("cURL Error # {$err_no}: ".$error, $responseCode);
        }

        return array(
            'response' => $response,
            'curl_info' => $curlInfo,
        );
    }

    /**
     * Method to check if HTTP transport curl is available for use.
     *
     * @return bool
     */
    public function isSupportedTransport()
    {
        return function_exists('curl_version') && curl_version();
    }

    /**
     * The Supported for cURL HTTP requests.
     *
     * @return array
     */
    public function getSupportedHttpRequest()
    {
        return array(
            self::HTTP_GET,
            self::HTTP_POST,
            self::HTTP_PATCH,
            self::HTTP_PUT,
            self::HTTP_DELETE,
            self::HTTP_HEAD,
        );
    }
}
