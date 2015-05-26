elasticsearch-guzzle5connection
===============================

[![Latest Stable Version](https://poser.pugx.org/iwai/elasticsearch-guzzle5connection/v/stable)](https://packagist.org/packages/iwai/elasticsearch-guzzle5connection) [![Total Downloads](https://poser.pugx.org/iwai/elasticsearch-guzzle5connection/downloads)](https://packagist.org/packages/iwai/elasticsearch-guzzle5connection) [![Latest Unstable Version](https://poser.pugx.org/iwai/elasticsearch-guzzle5connection/v/unstable)](https://packagist.org/packages/iwai/elasticsearch-guzzle5connection) [![License](https://poser.pugx.org/iwai/elasticsearch-guzzle5connection/license)](https://packagist.org/packages/iwai/elasticsearch-guzzle5connection) [![Build Status](https://travis-ci.org/iwai/elasticsearch-guzzle5connection.svg?branch=master)](https://travis-ci.org/iwai/elasticsearch-guzzle5connection)

Async support for elasticsearch-php

* Unsupported logging

## Install

```javascript
{
    "require": {
        "elasticsearch/elasticsearch": "~1.0",
        "iwai/elasticsearch-guzzle5connection": "~1.0"
    }
}
```

## Example

### Transparent async request 

```php
use Elasticsearch\Client as ESClient;

$client = new ESClient([
    'hosts' => [ '127.0.0.1:9200' ],
    'connectionClass' => '\Iwai\Elasticsearch\Guzzle5Connection',
    'serializerClass' => '\Iwai\Elasticsearch\FutureSerializer'
]);

$response = $client->get([
    'index' => 'index_name',
    'type'  => 'type',
    'id'    => '1',
]);

echo $response['hits']['total'];

```

### Explicit wait request  

```php
use Elasticsearch\Client as ESClient;

$client = new ESClient([
    'hosts' => [ '127.0.0.1:9200' ],
    'connectionClass' => '\Iwai\Elasticsearch\Guzzle5Connection',
    'serializerClass' => '\Iwai\Elasticsearch\FutureSerializer'
]);

$future = $client->get([
    'index' => 'index_name',
    'type'  => 'type',
    'id'    => '1',
]);

$response = $future->wait();

echo $response['hits']['total'];

```

### Promise style  

```php
use Elasticsearch\Client as ESClient;

$client = new ESClient([
    'hosts' => [ '127.0.0.1:9200' ],
    'connectionClass' => '\Iwai\Elasticsearch\Guzzle5Connection',
    'serializerClass' => '\Iwai\Elasticsearch\FutureSerializer'
]);

$future = $client->get([
    'index' => 'index_name',
    'type'  => 'type',
    'id'    => '1',
]);

$futureData->then(function ($response) {
    echo $response['hits']['total'];
});

```

### With RingPHP

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

```
