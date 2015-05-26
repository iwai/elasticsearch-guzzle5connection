<?php
namespace Iwai\Guzzle5Connection\Test;

use Codeception\Util\Stub;
use Iwai\Elasticsearch\Guzzle5Connection;

use React\EventLoop;
use WyriHaximus\React\RingPHP\HttpClientAdapter;

class Guzzle5ConnectionIntegrationTest extends \Codeception\TestCase\Test
{
    /**
     * @var \Iwai\Guzzle5Connection\Test\UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }


    public function testResponseFutureResultOnRingPHP()
    {
        $log = $trace = Stub::make('\Monolog\Logger', []);
        $connection = new Guzzle5Connection([
            'host' => '127.0.0.1',
            'port' => 9200,
        ], null, $log, $trace);

        /** @var \Iwai\Elasticsearch\FutureResult $response */
        $response = $connection->performRequest('GET', '/');

        $this->assertInstanceOf('Iwai\Elasticsearch\FutureResult', $response);

        $response->wait();

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('200', $response['status']);
    }

    /**
     * @expectedException \GuzzleHttp\Exception\ConnectException
     */
    public function testFailConnection()
    {
        $log = $trace = Stub::make('\Monolog\Logger', []);
        $connection = new Guzzle5Connection([
            'host' => 'localhost5',
            'port' => 9200,
        ], null, $log, $trace);

        /** @var \Iwai\Elasticsearch\FutureResult $response */
        $response = $connection->performRequest('GET', '/');

        $this->assertInstanceOf('Iwai\Elasticsearch\FutureResult', $response);

        $response->wait();
    }

}