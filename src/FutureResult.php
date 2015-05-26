<?php
/**
 * FutureResult.php
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
 * @property ResponseInterface|FutureInterface $_value
 * @method   int   getStatusCode()
 * @method   array  getHeaders()
 * @method   string getHeader($header)
 */
class FutureResult implements \ArrayAccess, \Countable, ToArrayInterface, FutureInterface
{
    use MagicFutureTrait;

    private static $compatibleKeys = [ 'status', 'text', 'info' ];

    private $esresult = [];


    public function offsetGet($offset)
    {
        if (!in_array($offset, self::$compatibleKeys)) {
            error_log(sprintf('Notice: Undefined offset: %s', $offset));
        }
        return $this->compatibleValue($offset);
    }

    public function offsetSet($offset, $value)
    {
        throw new \Exception(sprintf('Invalid set read only: %s', $offset));
    }

    public function offsetExists($offset)
    {
        return in_array($offset, self::$compatibleKeys);
    }

    public function offsetUnset($offset)
    {
        throw new \Exception(sprintf('Invalid unset read only: %s', $offset));
    }

    public function toArray()
    {
        return [
            'status' => $this->getFutureStatus(),
            'info'   => $this->getFutureInfo(),
            'text'   => $this->getFutureText(),
        ];
    }

    public function count()
    {
        return count($this->toArray());
    }

    public function __call($name, $arguments)
    {
        if (!method_exists($this->_value, $name))
            throw new BadMethodCallException();

        return call_user_func([$this->_value, $name], $arguments);
    }

    private function getFutureStatus()
    {
        if (!isset($this->esresult['status']))
            return $this->esresult['status'] = new FutureStatus(
                $this, [ $this, 'wait' ], [ $this, 'cancel' ]
            );

        return $this->esresult['status'];
    }

    private function getFutureInfo()
    {
        if (!isset($this->esresult['info']))
            return $this->esresult['info'] = new FutureInfo(
                $this, [ $this, 'wait' ], [ $this, 'cancel' ]
            );

        return $this->esresult['info'];
    }

    private function getFutureText()
    {
        return $this->esresult['text'] = $this;
    }

    private function compatibleValue($name)
    {
        switch ($name)
        {
            case 'status':
                return $this->getFutureStatus();

            case 'info':
                return $this->getFutureInfo();

            case 'text':
                return $this->getFutureText();

            default:
                return $this;
        }
    }



}