<?php
/**
 * FutureInfo.php
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

/**
 * @property FutureResult $_value
 */
class FutureInfo  implements \ArrayAccess, \Countable, ToArrayInterface, FutureInterface
{

    use MagicFutureTrait;


    public function offsetGet($offset)
    {
        return $this->_value->getHeader($offset);
    }

    public function offsetSet($offset, $value)
    {
        throw new \Exception(sprintf('Invalid set read only: %s', $offset));
    }

    public function offsetExists($offset)
    {
        return $this->_value->offsetExists($offset);
    }

    public function offsetUnset($offset)
    {
        throw new \Exception(sprintf('Invalid unset read only: %s', $offset));
    }

    public function toArray()
    {
        return $this->_value->getHeaders();
    }

    public function count()
    {
        return count($this->_value->getHeaders());
    }


    public function __toString()
    {
        try {
            return $this->_value->getHeaders();
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return '';
        }
    }

} 