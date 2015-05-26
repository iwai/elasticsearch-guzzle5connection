<?php
/**
 * FutureData.php
 *
 * @version     $Id$
 *
 */


namespace Iwai\Elasticsearch;

use Elasticsearch\Common\Exceptions\BadMethodCallException;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Ring\Future\FutureInterface;
use GuzzleHttp\Ring\Future\MagicFutureTrait;
use GuzzleHttp\ToArrayInterface;

class FutureData implements \ArrayAccess, \Countable, ToArrayInterface, FutureInterface {

    use MagicFutureTrait {
        MagicFutureTrait::wait as parentWait;
    }

    public function wait()
    {
        return $this->parentWait();
    }

    public function offsetGet($offset)
    {
        return $this->_value[ $offset ];
    }

    public function offsetSet($offset, $value)
    {
        $this->_value[ $offset ] = $value;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_value);
    }

    public function offsetUnset($offset)
    {
        unset($this->_value[$offset]);
    }

    public function toArray()
    {
        return $this->_value;
    }

    public function count()
    {
        return count($this->_value);
    }

    public function __call($name, $arguments)
    {
        if (!method_exists($this->_value, $name))
            throw new BadMethodCallException();

        return call_user_func([$this->_value, $name], $arguments);
    }


    public function __toString()
    {
        try {
            return (string)$this->_value;
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return '';
        }
    }

} 