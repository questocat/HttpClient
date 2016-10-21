<?php
/**
 * Created by PhpStorm.
 * Author: PhilPu <zhengchaopu@gmail.com>
 * Date: 2016/7/25
 */
namespace HttpClient\Uri;

use InvalidArgumentException;

/**
 * Class Uri
 *
 * @package HttpClient\Uri
 * @link    http://www.faqs.org/rfcs/rfc3986.html
 */
class Uri implements UriInterface
{
    /**
     * @var string
     */
    protected $scheme = '';

    /**
     * @var string
     */
    protected $user = '';

    /**
     * @var string
     */
    protected $pass = '';

    /**
     * @var string
     */
    protected $host = '';

    /**
     * @var
     */
    protected $port;

    /**
     * @var string
     */
    protected $path = '';

    /**
     * @var string
     */
    protected $query = '';

    /**
     * @var string
     */
    protected $fragment = '';

    /**
     * @var bool
     */
    protected $explicitTrailingHostSlash = false;

    /**
     * Uri constructor.
     *
     * @param null $uri
     */
    public function __construct($uri = null)
    {
        if (!empty($uri)) {
            $this->parseUri($uri);
        }
    }

    /**
     * @param $uri
     *
     * @throws InvalidArgumentException
     */
    protected function parseUri($uri)
    {
        if (!is_string($uri)) {
            throw new InvalidArgumentException('Uri must be a string');
        }

        if (false === ($parts = parse_url($uri))) {
            throw new InvalidArgumentException('Malformed URL: ', $uri);
        }

        if (!isset($parts['scheme'])) {
            throw new InvalidArgumentException('Invalid URI: http|https scheme required');
        }

        $parts = $this->getUriParts($parts);

        $this->scheme = $parts['scheme'];
        $this->user = $parts['user'];
        $this->pass = $parts['pass'];
        $this->host = $parts['host'];
        $this->port = $parts['port'];
        $this->path = $parts['path'];
        $this->query = $parts['query'];
        $this->fragment = $parts['fragment'];
    }

    /**
     * @return string
     */
    public function getAbsoluteUri()
    {
        $uri = $this->scheme.'://'.$this->getAuthority();

        if (!empty($this->path)) {
            $uri .= $this->path;
        }

        if (!empty($this->query)) {
            $uri .= '?'.$this->query;
        }

        if (!empty($this->fragment)) {
            $uri .= '#'.$this->fragment;
        }

        return $uri;
    }

    /**
     * @return mixed
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @return mixed
     */
    public function getAuthority()
    {
        $userInfo = $this->getUserInfo();
        $host = $this->getHost();
        $port = $this->getPort();

        return ($userInfo ? $userInfo.'@' : '').$host.($port ? ':'.$port : '');
    }

    /**
     * @return mixed
     */
    public function getUserInfo()
    {
        return $this->user.($this->pass ? ':'.$this->pass : '');
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return mixed
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @param $scheme
     *
     * @return mixed
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * @param $user
     * @param string $pass
     *
     * @return $this
     */
    public function setUserInfo($user, $pass = null)
    {
        $this->user = $user;
        $this->pass = $pass ? $pass : '';

        return $this;
    }

    /**
     * @param $host
     *
     * @return mixed
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @param $port
     *
     * @return mixed
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @param $path
     *
     * @return mixed
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @param $query
     *
     * @return mixed
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @param $fragment
     *
     * @return mixed
     */
    public function setFragment($fragment)
    {
        $this->fragment = $fragment;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSsl()
    {
        return strcmp('https', $this->getScheme()) ? false : true;
    }

    /**
     * @param $scheme
     *
     * @return int
     */
    public function getPortByScheme($scheme)
    {
        return strcmp('http', $scheme) ? 443 : 80;
    }

    /**
     * @return bool
     */
    public function hasExplicitTrailingHostSlash()
    {
        return $this->explicitTrailingHostSlash;
    }

    /**
     * Get Multi parts of uri.
     *
     * @param array $parts
     *
     * @return array
     */
    public function getMultiParts(
        array $parts = array('scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment')
    ) {
        $uri = '';
        $uri .= in_array('scheme', $parts) ? (!empty($this->scheme) ? $this->scheme.'://' : '') : '';
        $uri .= in_array('user', $parts) ? $this->user : '';
        $uri .= in_array(
            'pass',
            $parts
        ) ? (!empty($this->pass) ? ':' : '').$this->pass.(!empty($this->user) ? '@' : '') : '';
        $uri .= in_array('host', $parts) ? $this->host : '';
        $uri .= in_array('port', $parts) ? (!empty($this->port) ? ':' : '').$this->port : '';
        $uri .= in_array('path', $parts) ? $this->path : '';
        $uri .= in_array('query', $parts) ? (!empty($this->query) ? '?'.$this->query : '') : '';
        $uri .= in_array('fragment', $parts) ? (!empty($this->fragment) ? '#'.$this->fragment : '') : '';

        return $uri;
    }

    /**
     * @param array $parts
     *
     * @return array
     */
    protected function getUriParts(array $parts)
    {
        $parts = array_replace(
            array(
                'scheme' => '',
                'user' => '',
                'pass' => '',
                'host' => '',
                'port' => '',
                'path' => '',
                'query' => '',
                'fragment' => '',
            ),
            $parts
        );

        if (empty($parts['port'])) {
            $parts['port'] = $this->getPortByScheme($parts['scheme']);
        }

        if (empty($parts['path'])) {
            $parts['path'] = '/';
            $this->explicitTrailingHostSlash = false;
        } else {
            if ('/' === $parts['path']) {
                $this->explicitTrailingHostSlash = true;
            }
        }

        return $parts;
    }

    /**
     * Uses protected user info by default as per rfc3986-3.2.1
     *
     * @return string
     */
    public function __toString()
    {
        $uri = $this->scheme.'://'.$this->getAuthority();

        if ('/' === $this->path) {
            $uri .= $this->explicitTrailingHostSlash ? '/' : '';
        } else {
            $uri .= $this->path;
        }

        $uri .= !empty($this->query) ? "?{$this->query}" : '';
        $uri .= !empty($this->fragment) ? "#{$this->fragment}" : '';

        return $uri;
    }
}