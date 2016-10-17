<?php
/**
 * Created by PhpStorm.
 * Author: PhilPu <zhengchaopu@gmail.com>
 * Date: 2016/10/13
 */
namespace HttpClient;

use InvalidArgumentException;

/**
 * Class HttpManager
 * @package HttpClient
 */
class HttpManager
{
    /**
     * @var array
     */
    protected $config = array();

    /**
     * @var array
     */
    protected $transports = array();

    /**
     * @var array
     */
    protected $supportedTransports = array(
        'curl' => 'Curl',
        'socket' => 'Socket',
        'stream' => 'Stream'
    );

    /**
     * HttpManager constructor.
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        $this->config = $config;
    }

    /**
     * @param null $transport
     * @return mixed
     */
    public function transport($transport = null)
    {
        $transport = $transport ? $transport : $this->getDefaultTransport();

        if (!isset($this->transports[$transport])) {
            $this->transports[$transport] = $this->createTransport($transport);
        }

        return $this->transports[$transport];
    }

    /**
     * @param $transport
     * @return mixed
     */
    protected function createTransport($transport)
    {
        if (isset($this->supportedTransports[$transport])) {
            $transport = $this->supportedTransports[$transport];
            $transportFullPath = __NAMESPACE__ . '\\Transports\\' . $transport . 'Transport';
            return $this->buildTransport($transportFullPath, $this->config);
        }

        throw new InvalidArgumentException("Transport [$transport] not supported.");
    }

    /**
     * Build a transport instance.
     * @param $transport
     * @param $config
     * @return mixed
     */
    protected function buildTransport($transport, $config)
    {
        return new $transport($config);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getDefaultTransport()
    {
        throw new InvalidArgumentException('No Http transport was specified.');
    }
}
