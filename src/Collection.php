<?php
/**
 * Created by PhpStorm.
 * Author: PhilPu <zhengchaopu@gmail.com>
 * Date: 2016/10/27.
 */
namespace HttpClient;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Serializable;

/**
 * Class Collection.
 */
class Collection implements ArrayAccess, IteratorAggregate, Countable, JsonSerializable, Serializable
{
    /**
     * Data associated with the object.
     *
     * @var array
     */
    protected $items = array();

    /**
     * Collection constructor.
     *
     * @param array|null $items
     */
    public function __construct(array $items = null)
    {
        if ($items) {
            $this->items = $items;
        }
    }

    /**
     * Convert the object to json.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Get all of the items in the collection.
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Add a value to a key.
     *
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function add($key, $value)
    {
        if (!array_key_exists($key, $this->items)) {
            if (is_null($key)) {
                $this->items[] = $value;
            } else {
                $this->items[$key] = $value;
            }
        } else {
            if (!is_array($this->items[$key])) {
                $this->items[$key] = array(
                    $this->items[$key],
                    $value,
                );
            } else {
                $this->items[$key][] = $value;
            }
        }

        return $this;
    }

    /**
     * Set a value to a key.
     *
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function set($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }

        return $this;
    }

    /**
     * Get a specific key value.
     *
     * @param      $key
     * @param null $default
     *
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->items[$key];
        }

        return $default;
    }

    /**
     * Remove a specific key value pair.
     *
     * @param $key
     *
     * @return $this
     */
    public function remove($key)
    {
        if ($this->has($key)) {
            unset($this->items[$key]);
        }

        return $this;
    }

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param $key
     *
     * @return bool
     */
    public function has($key)
    {
        if (array_key_exists($key, $this->items)) {
            return $key;
        }

        return false;
    }

    /**
     * Retrieve the first an item.
     *
     * @return mixed
     */
    public function first()
    {
        return reset($this->items);
    }

    /**
     * Retrieve the last an item.
     *
     * @return bool
     */
    public function last()
    {
        $end = end($this->items);
        reset($this->items);

        return $end;
    }

    /**
     * Removes all key value pairs.
     *
     * @return $this
     */
    public function clear()
    {
        $this->items = array();

        return $this;
    }

    /**
     * Build to array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->all();
    }

    /**
     * Build to json.
     *
     * @param int $option
     *
     * @return string
     */
    public function toJson($option = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($this->all(), $option);
    }

    /**
     * Determine if an items exists at an offset.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Get an items at a given offset.
     *
     * @param mixed $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Set the items at a given offset.
     *
     * @param mixed $offset
     * @param mixed $value
     *
     * @return Collection
     */
    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }

    /**
     * Unset the items at a given offset.
     *
     * @param mixed $offset
     *
     * @return Collection
     */
    public function offsetUnset($offset)
    {
        return $this->remove($offset);
    }

    /**
     * Get an iterator object.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Return the number of keys.
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Convert the object into something JSON serializable.
     * PHP 5 >= 5.4.0.
     *
     * @return mixed
     */
    public function jsonSerialize()
    {
        return $this->items;
    }

    /**
     * String representation of object.
     *
     * @return string
     */
    public function serialize()
    {
        return serialize($this->items);
    }

    /**
     * Constructs the object.
     *
     * @param string $serialized
     *
     * @return mixed
     */
    public function unserialize($serialized)
    {
        return $this->items = unserialize($serialized);
    }
}
