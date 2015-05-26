<?php
namespace Iwai\Guzzle5Connection\Test;

use React\EventLoop;
use WyriHaximus\React\RingPHP\HttpClientAdapter;
use Elasticsearch\Client as ESClient;

class ESClientWithRingPHPIntegrationTest extends \Codeception\TestCase\Test
{
    /**
     * @var \Iwai\Guzzle5Connection\Test\UnitTester
     */
    protected $tester;

    /** @var \Elasticsearch\Client $info */
    protected $client;

    protected function _before()
    {
        $loop  = EventLoop\Factory::create();

        $this->client = new ESClient([
            'hosts' => [ '127.0.0.1:9200' ],
            'connectionClass' => '\Iwai\Elasticsearch\Guzzle5Connection',
            'serializerClass' => '\Iwai\Elasticsearch\FutureSerializer',
            'connectionParams' => [ 'ringphp_handler' => new HttpClientAdapter($loop) ]
        ]);
    }

    protected function _after()
    {
    }

    public function testPing()
    {
        $this->markTestSkipped('Unsupported yet');

        $this->assertTrue($this->client->ping());
    }

    public function testInfo()
    {
        /** @var \Iwai\Elasticsearch\FutureData $info */
        $info = $this->client->info();

        $response = $info->wait();

        $this->assertNotEmpty($response);
        $this->assertEquals('array', gettype($response));
    }

    public function testCreateIndex()
    {
        $indexParams['index'] = 'my_index';
        $indexParams['body']['settings']['number_of_shards'] = 2;
        $indexParams['body']['settings']['number_of_replicas'] = 0;

        $response = $this->client->indices()->create($indexParams);

        $this->assertInstanceOf('Iwai\Elasticsearch\FutureData', $response);
        // block wait
        $this->assertEquals(1, $response['acknowledged']);
    }

    public function testIndexDocument()
    {
        $response = $this->client->index([
            'index' => 'my_index',
            'type'  => 'my_type',
            'id'    => 'my_id',
            'body' => [ 'testField' => 'abc' ]
        ]);

        $this->assertInstanceOf('Iwai\Elasticsearch\FutureData', $response);

        $this->assertEquals(1, $response['created']);
    }

    public function testIndexDocument2()
    {
        /** @var \Iwai\Elasticsearch\FutureData $future */
        $future = $this->client->index([
            'index' => 'my_index',
            'type'  => 'my_type',
            'id'    => 'my_id2',
            'body' => [ 'testField' => 'abc' ]
        ]);

        $future->then(function ($response) {
            $this->assertEquals(1, $response['created']);
        });
        $response = $future->wait();

    }

    public function testDeleteIndex()
    {
        $response = $this->client->indices()->delete(['index' => 'my_index']);

        $this->assertEquals(1, $response['acknowledged']);
    }

}