<?php
/**
 * Guzzle5Connection.php
 *
 * @version     $Id$
 *
 */


namespace Iwai\Elasticsearch;

use Elasticsearch\Connections\AbstractConnection;
use Elasticsearch\Connections\ConnectionInterface;

use GuzzleHttp\Client;
use GuzzleHttp\Message\FutureResponse;

use Guzzle\Http\Message\Request;
use Psr\Log\LoggerInterface;

class Guzzle5Connection extends AbstractConnection implements ConnectionInterface
{

    /** @var  Client */
    private $guzzle;

    private $connectionOpts = array();

    private $lastRequest = array();


    /**
     * @param array                    $hostDetails
     * @param array                    $connectionParams Array of connection parameters
     * @param \Psr\Log\LoggerInterface $log              logger object
     * @param \Psr\Log\LoggerInterface $trace            logger object (for curl traces)
     *
     * @throws \Elasticsearch\Common\Exceptions\InvalidArgumentException
     * @return \Elasticsearch\Connections\GuzzleConnection
     */
    public function __construct($hostDetails, $connectionParams, LoggerInterface $log, LoggerInterface $trace)
    {
        if (isset($hostDetails['port']) !== true) {
            $hostDetails['port'] = 9200;
        }

        if (isset($hostDetails['scheme']) !== true) {
            $hostDetails['scheme'] = 'http';
        }

        $handler = null;

        if (isset($connectionParams)) {
            if (isset($connectionParams['ringphp_handler'])) {
                $handler = $connectionParams['ringphp_handler'];
                unset($connectionParams['ringphp_handler']);
            }

            $this->connectionOpts = $connectionParams;
        }

        if ($handler) {
            $this->guzzle = new \GuzzleHttp\Client([
                'handler'  => $handler,
                'defaults' => [ 'future' => true ]
            ]);
        } else {
            $this->guzzle = new \GuzzleHttp\Client([
                'defaults' => [ 'future' => true ]
            ]);
        }

        return parent::__construct($hostDetails, $connectionParams, $log, $trace);

    }

    /**
     * Returns the transport schema
     *
     * @return string
     */
    public function getTransportSchema()
    {
        return $this->transportSchema;
    }


    /**
     * Perform an HTTP request on the cluster
     *
     * @param string      $method HTTP method to use for request
     * @param string      $uri    HTTP URI to use for request
     * @param null|string $params Optional URI parameters
     * @param null|string $body   Optional request body
     * @param array       $options
     *
     * @return array
     */
    public function performRequest($method, $uri, $params = null, $body = null, $options = array())
    {
        $uri = $this->getURI($uri, $params);

        $options += $this->connectionOpts;

        /** @var FutureResponse $response */
        $response = $this->sendRequest($method, $uri, $body, $options);

        return new FutureResult(
            $response->promise(),
            [ $response, 'wait' ],
            [ $response, 'cancel' ]
        );
    }


    /**
     * @return array
     */
    public function getLastRequestInfo()
    {
        return $this->lastRequest;
    }


    /**
     * @param string $uri
     * @param array $params
     *
     * @return string
     */
    private function getURI($uri, $params)
    {
        $uri = $this->host . $uri;

        if (isset($params) === true) {
            $uri .= '?' . http_build_query($params);
        }

        return $uri;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param string $body
     * @param array $options
     *
     * @return Request
     */
    private function sendRequest($method, $uri, $body, $options = array())
    {
        if ($method === 'GET' && isset($body) === true) {
            $method = 'POST';
        }

        if (isset($body) === true) {
            $this->lastRequest = array( 'request' => array(
                'uri'     => $uri,
                'body'    => $body,
                'options' => $options,
                'method'  => $method
            ));
            $response = $this->guzzle->$method(
                $uri, array_merge($options, [ 'body' => $body ])
            );
        } else {
            $this->lastRequest = array( 'request' => array(
                'uri'     => $uri,
                'body'    => null,
                'options' => $options,
                'method'  => $method
            ));
            $response = $this->guzzle->$method($uri, $options);
        }

        return $response;
    }

} 