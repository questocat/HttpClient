<?php
/**
 * Created by PhpStorm.
 * Author: PhilPu <zhengchaopu@gmail.com>
 * Date: 2016/10/26.
 */

namespace HttpClient\Uri;

/**
 * Class UriFactory.
 */
class UriFactory implements UriFactoryInterface
{
    /**
     * @param $absoluteUri
     *
     * @return Uri
     */
    public function createFromAbsolute($absoluteUri)
    {
        return new Uri($absoluteUri);
    }
}
