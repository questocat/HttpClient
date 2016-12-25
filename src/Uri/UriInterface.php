<?php
/**
 * Created by PhpStorm.
 * Author: PhilPu <zhengchaopu@gmail.com>
 * Date: 2016/7/25.
 */

namespace HttpClient\Uri;

/**
 * Interface UriInterface.
 */
interface UriInterface
{
    /**
     * @return mixed
     */
    public function getScheme();

    /**
     * @return mixed
     */
    public function getAuthority();

    /**
     * @return mixed
     */
    public function getUserInfo();

    /**
     * @return mixed
     */
    public function getHost();

    /**
     * @return mixed
     */
    public function getPort();

    /**
     * @return mixed
     */
    public function getPath();

    /**
     * @return mixed
     */
    public function getQuery();

    /**
     * @return mixed
     */
    public function getFragment();

    /**
     * @param $scheme
     *
     * @return mixed
     */
    public function setScheme($scheme);

    /**
     * @param      $user
     * @param null $password
     *
     * @return mixed
     */
    public function setUserInfo($user, $password = null);

    /**
     * @param $host
     *
     * @return mixed
     */
    public function setHost($host);

    /**
     * @param $port
     *
     * @return mixed
     */
    public function setPort($port);

    /**
     * @param $path
     *
     * @return mixed
     */
    public function setPath($path);

    /**
     * @param $query
     *
     * @return mixed
     */
    public function setQuery($query);

    /**
     * @param $fragment
     *
     * @return mixed
     */
    public function setFragment($fragment);
}
