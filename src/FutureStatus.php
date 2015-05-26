<?php
/**
 * FutureStatus.php
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
class FutureStatus implements FutureInterface
{

    use MagicFutureTrait;

    public function __toString()
    {
        try {
            return (string)$this->_value->getStatusCode();
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return '';
        }
    }

}