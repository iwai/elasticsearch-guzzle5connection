<?php
/**
 * FutureSerializer.php
 *
 * @copyright   Copyright (c) 2014 sonicmoov Co.,Ltd.
 * @version     $Id$
 *
 */


namespace Iwai\Elasticsearch;


use Elasticsearch\Common\Exceptions\Serializer\JsonErrorException;

class FutureSerializer {


    /**
     * Serialize assoc array into JSON string
     *
     * @param string|array $data Assoc array to encode into JSON
     *
     * @return string
     */
    public function serialize($data)
    {
        if (is_string($data) === true) {
            return $data;
        } else {
            $data = json_encode($data);
            if ($data === '[]') {
                return '{}';
            } else {
                return $data;
            }
        }
    }

    /**
     * @param string $data
     * @param array  $headers
     *
     * @return array
     */
    public function deserialize($data, $headers)
    {
        if ($data instanceof FutureResult) {

            $data->then(function ($response) {
                /** @var \GuzzleHttp\Message\Response $response */
                if (strpos((string)$response->getHeader('Content-Type'), 'json') !== false) {
                    return $this->decode($response->getBody());
                } else {
                    //Not json, return as string
                    return $response->getBody();
                }
            });

            return new FutureData(
                $data,
                [ $data, 'wait' ],
                [ $data, 'cancel' ]
            );
        } else {

            if (isset($headers['content_type']) === true) {
                if (strpos($headers['content_type'], 'json') !== false) {
                    return $this->decode($data);
                } else {
                    // Not json, return as string
                    return $data;
                }
            } else {
                //No content headers, assume json
                return $this->decode($data);
            }
        }
    }

    /**
     * @todo For 2.0, remove the E_NOTICE check before raising the exception.
     *
     * @param $data
     *
     * @return array
     * @throws JsonErrorException
     */
    private function decode($data)
    {
        $result = @json_decode($data, true);

        // Throw exception only if E_NOTICE is on to maintain backwards-compatibility on systems that silently ignore E_NOTICEs.
        if (json_last_error() !== JSON_ERROR_NONE && (error_reporting() & E_NOTICE) === E_NOTICE) {
            $e = new JsonErrorException(json_last_error(), $data, $result);
            throw $e;
        }

        return $result;
    }

} 