elasticsearch-guzzle5connection
===============================

Async support for elasticsearch-php

* Unsupported logging

## Example

```php
use React\EventLoop;
use WyriHaximus\React\RingPHP\HttpClientAdapter;
use Elasticsearch\Client as ESClient;

$loop  = EventLoop\Factory::create();

$client = new ESClient([
    'hosts' => [ '127.0.0.1:9200' ],
    'connectionClass' => '\Iwai\Elasticsearch\Guzzle5Connection', // required
    'serializerClass' => '\Iwai\Elasticsearch\FutureSerializer',  // required
    'connectionParams' => [ 'ringphp_handler' => new HttpClientAdapter($loop) ] // optional
]);

$futureData = $client->get([
    'index' => 'index_name',
    'type'  => 'type',
    'id'    => '1',
]);

$futureData->then(function ($response) {
    echo $response['hits']['total'];
});

$loop->run();

// or this is blocking
echo $response['hits']['total'];

```
