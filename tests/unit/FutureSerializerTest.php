<?php
namespace Iwai\Guzzle5Connection\Test;


use Iwai\Elasticsearch\FutureSerializer;

class FutureSerializerTest extends \Codeception\TestCase\Test
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
        $this->assertInstanceOf(
            'Iwai\Elasticsearch\FutureSerializer', new FutureSerializer()
        );
    }

}