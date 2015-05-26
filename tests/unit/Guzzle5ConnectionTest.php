<?php
namespace Iwai\Guzzle5Connection\Test;

use Codeception\Util\Stub;
use Iwai\Elasticsearch\Guzzle5Connection;

class Guzzle5ConnectionTest extends \Codeception\TestCase\Test
{
    /**
     * @var \Iwai\Guzzle5Connection\Test\UnitTester
     */
    protected $unitTester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function testCreateInstance()
    {
        $log = $trace = Stub::make('\Monolog\Logger', []);
        $connection = new Guzzle5Connection(
            ['host' => 'localhost', 'port' => 9200], null, $log, $trace
        );

        $this->assertInstanceOf('Iwai\Elasticsearch\Guzzle5Connection', $connection);
    }

    public function testCreateInstanceWithRingPHP()
    {
        $log = $trace    = Stub::make('\Monolog\Logger', []);
        $ringphp_handler = Stub::make('WyriHaximus\React\RingPHP\HttpClientAdapter');

        $connection = new Guzzle5Connection([
            'host' => 'localhost', 'port' => 9200, 'ringphp_handler' => $ringphp_handler
        ], null, $log, $trace);


        $this->assertInstanceOf('Iwai\Elasticsearch\Guzzle5Connection', $connection);
    }

//    public function testResponseFutureResult()
//    {
//        $log = $trace = Stub::make('\Monolog\Logger', []);
//        $connection = new Guzzle5Connection(
//            ['host' => 'localhost', 'port' => 9200], null, $log, $trace
//        );
//
//        $response = $connection->performRequest('GET', '/');
//
//        $this->assertInstanceOf('Iwai\Elasticsearch\FutureResult', $response);
//
//    }

}