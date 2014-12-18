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

class FutureData  implements \ArrayAccess, \Countable, ToArrayInterface, FutureInterface {

    use MagicFutureTrait {
        MagicFutureTrait::wait as parentWait;
    }

    public function wait()
    {
        return $this->parentWait();
    }

    public function offsetGet($offset)
    {
        return $this->_value->offsetGet($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->_value->offsetSet($offset, $value);
    }

    public function offsetExists($offset)
    {
        return $this->_value->offsetExists($offset);
    }

    public function offsetUnset($offset)
    {
        $this->_value->offsetUnset($offset);
    }

    public function toArray()
    {
        return $this->_value->toArray();
    }

    public function count()
    {
        return $this->_value->count();
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
            return (string) $this->_value;
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return '';
        }
    }

} 