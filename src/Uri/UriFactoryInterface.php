<?php
/**
 * Created by PhpStorm.
 * Author: PhilPu <zhengchaopu@gmail.com>
 * Date: 2016/10/26.
 */
namespace HttpClient\Uri;

/**
 * Interface UriFactoryInterface.
 */
interface UriFactoryInterface
{
    /**
     * @param $absoluteUri
     *
     * @return mixed
     */
    public function createFromAbsolute($absoluteUri);

}
