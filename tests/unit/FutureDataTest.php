<?php
namespace Iwai\Guzzle5Connection\Test;

use Codeception\Util\Stub;

use Iwai\Elasticsearch\FutureData;

class FutureDataTest extends \Codeception\TestCase\Test
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

    public function testCreateInstance()
    {
        $promise = Stub::make('\React\Promise\Promise', []);
        $this->assertInstanceOf(
            'Iwai\Elasticsearch\FutureData', new FutureData($promise)
        );
    }

}