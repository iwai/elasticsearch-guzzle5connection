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

use Elasticsearch\Common\Exceptions\AlreadyExpiredException;
use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Elasticsearch\Common\Exceptions\Conflict409Exception;
use Elasticsearch\Common\Exceptions\Forbidden403Exception;
use Elasticsearch\Common\Exceptions\InvalidArgumentException;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Common\Exceptions\NoDocumentsToGetException;
use Elasticsearch\Common\Exceptions\NoShardAvailableException;
use Elasticsearch\Common\Exceptions\RoutingMissingException;
use Elasticsearch\Common\Exceptions\ScriptLangNotSupportedException;
use Elasticsearch\Common\Exceptions\TransportException;

use GuzzleHttp\Client;
use GuzzleHttp\Message\FutureResponse;
use WyriHaximus\React\RingPHP\HttpClientAdapter;

//use \Guzzle\Http\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\ServerErrorResponseException;
use Guzzle\Http\Message\Header\HeaderCollection;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
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


    /**
     * @param Request                      $request
     * @param ServerErrorResponseException $exception
     * @param string                       $body
     *
     * @throws \Elasticsearch\Common\Exceptions\RoutingMissingException
     * @throws \Elasticsearch\Common\Exceptions\NoShardAvailableException
     * @throws \Guzzle\Http\Exception\ServerErrorResponseException
     * @throws \Elasticsearch\Common\Exceptions\NoDocumentsToGetException
     */
    private function process5xxError(Request $request, ServerErrorResponseException $exception, $body)
    {
        $this->logErrorDueToFailure($request, $exception, $body);

        $statusCode    = $request->getResponse()->getStatusCode();
        $exceptionText = $exception->getMessage();
        $responseBody  = $request->getResponse()->getBody(true);

        $exceptionText = "$statusCode Server Exception: $exceptionText\n$responseBody";
        $this->log->error($exceptionText);

        if ($statusCode === 500 && strpos($responseBody, "RoutingMissingException") !== false) {
            throw new RoutingMissingException($responseBody, $statusCode, $exception);
        } elseif ($statusCode === 500 && preg_match('/ActionRequestValidationException.+ no documents to get/',$responseBody) === 1) {
            throw new NoDocumentsToGetException($responseBody, $statusCode, $exception);
        } elseif ($statusCode === 500 && strpos($responseBody, 'NoShardAvailableActionException') !== false) {
            throw new NoShardAvailableException($responseBody, $statusCode, $exception);
        } else {
            throw new \Elasticsearch\Common\Exceptions\ServerErrorResponseException($responseBody, $statusCode, $exception);
        }


    }


    private function process4xxError(Request $request, ClientErrorResponseException $exception, $body)
    {
        $this->logErrorDueToFailure($request, $exception, $body);

        $statusCode    = $request->getResponse()->getStatusCode();
        $exceptionText = $exception->getMessage();
        $responseBody  = $request->getResponse()->getBody(true);

        $exceptionText = "$statusCode Server Exception: $exceptionText\n$responseBody";

        if ($statusCode === 400 && strpos($responseBody, "AlreadyExpiredException") !== false) {
            throw new AlreadyExpiredException($responseBody, $statusCode, $exception);
        } elseif ($statusCode === 403) {
            throw new Forbidden403Exception($responseBody, $statusCode, $exception);
        } elseif ($statusCode === 404) {
            throw new Missing404Exception($responseBody, $statusCode, $exception);
        } elseif ($statusCode === 409) {
            throw new Conflict409Exception($responseBody, $statusCode, $exception);
        } elseif ($statusCode === 400 && strpos($responseBody, 'script_lang not supported') !== false) {
            throw new ScriptLangNotSupportedException($responseBody. $statusCode);
        } elseif ($statusCode === 400) {
            throw new BadRequest400Exception($responseBody, $statusCode, $exception);
        }
    }


    /**
     * @param Request    $request
     * @param \Exception $exception
     * @param string     $body
     */
    private function logErrorDueToFailure(Request $request, \Exception $exception, $body)
    {
        $response     = $request->getResponse();
        $headers      = $request->getHeaders()->getAll();
        $info         = $response->getInfo();
        $responseBody = $response->getBody(true);
        $status       = $response->getStatusCode();

        $this->lastRequest['response']['body']    = $responseBody;
        $this->lastRequest['response']['info']    = $info;
        $this->lastRequest['response']['status']  = $status;

        $this->logRequestFail(
            $request->getMethod(),
            $request->getUrl(),
            $body,
            $headers,
            $response->getInfo('total_time'),
            $response->getStatusCode(),
            $responseBody,
            $exception->getMessage()
        );
    }


    /**
     * @param CurlException $exception\
     */
    private function processCurlError(CurlException $exception)
    {
        $error = 'Curl error: ' . $exception->getMessage();
        $this->log->error($error);
        $this->throwCurlException($exception->getErrorNo(), $exception->getError());
    }

    /**
     * @param Request $request
     * @param string  $body
     */
    private function processSuccessfulRequest(Request $request, $body)
    {
        $response     = $request->getResponse();
        $headers      = $request->getHeaders()->getAll();
        $responseBody = $response->getBody(true);
        $status       = $response->getStatusCode();

        $this->lastRequest['response']['body']    = $responseBody;
        $this->lastRequest['response']['info']    = $response->getInfo();
        $this->lastRequest['response']['status']  = $status;

        $this->logRequestSuccess(
            $request->getMethod(),
            $request->getUrl(),
            $body,
            $headers,
            $status,
            $responseBody,
            $response->getInfo('total_time')
        );
    }

} 