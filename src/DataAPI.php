<?php

namespace bitpart\dataapi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class DataAPI
{
    private $clientId = 'php-client';
    private $client;

    private $username = '';
    private $password = '';
    private $baseUrl = '';

    private $accessToken = '';
    private $expiresIn = '';
    private $remember = '';
    private $sessionId = '';

    /**
     * @param array $options
     */
    public function __construct(string $username, string $password, $baseUrl, array $options = [])
    {
        $this->username = $username;
        $this->password = $password;
        $this->baseUrl = $baseUrl;

        $op = [
            'base_uri' => $this->baseUrl,
            'timeout'  => 120.0,
        ];
        if (!empty($options)) {
            $op = array_merge($op, $options);
        }
        $this->client = new Client($op);
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function authentication(): string
    {
        try {
            $response = $this->client->request('post', 'authentication', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    'username' => $this->username,
                    'password' => $this->password,
                    'clientId' => $this->clientId,
                ]
            ]);
            $body = $response->getBody();
            $body = json_decode($body, true);
            $this->accessToken = $body['accessToken'];
            $this->expiresIn = $body['expiresIn'];
            $this->remember = $body['remember'];
            $this->sessionId = $body['sessionId'];
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                echo Psr7\Message::toString($e->getResponse());
            }
        }
        return $this->accessToken;
    }

    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getToken(): array
    {
        $response = $this->client->request('post', 'token', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'X-MT-Authorization' =>' MTAuth sessionId=' . $this->sessionId
            ],
            'form_params' => [
                'clientId' => $this->clientId
            ]
        ]);
        $body = $response->getBody();
        return json_decode($body, true);
    }

    /**
     * @param $objectName
     * @param $siteId
     * @param $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function list($objectName, $siteId, $params = null): array
    {
        $query = $params ? [
            'query' => $params
        ] : [];
        if ($params && isset($params['status']) && strpos($params['status'], 'Draft') !== false) {
            $query['headers'] = [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'X-MT-Authorization' =>' MTAuth accessToken=' . $this->accessToken
            ];
        }
        $response = $this->client->request('get', "sites/{$siteId}/{$objectName}", $query);
        $body = $response->getBody();
        return json_decode($body, true);
    }

    /**
     * @param $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function search($params = null): array
    {
        $query = $params ? [
            'query' => $params
        ] : [];
        $response = $this->client->request('get', 'search', $query);
        $body = $response->getBody();
        return json_decode($body, true);
    }

    /**
     * @param $objectName
     * @param $siteId
     * @param $entryId
     * @param $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get($objectName, $siteId, $entryId, $params = null): array
    {
        $query = $params ? [
            'query' => $params
        ] : [];
        $response = $this->client->request('get', "sites/{$siteId}/{$objectName}/{$entryId}", $query);
        $body = $response->getBody();
        return json_decode($body, true);
    }

    /**
     * @param $objectName
     * @param $siteId
     * @param $params
     * @param $publish
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function create($objectName, $siteId, $params = [], $publish): array
    {
        $response = $this->client->request('post', "sites/{$siteId}/{$objectName}", [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'X-MT-Authorization' =>' MTAuth accessToken=' . $this->accessToken
            ],
            'form_params' => [
                'entry' => json_encode($params),
                'publish' => $publish
            ],
            'debug' => true
        ]);
        $body = $response->getBody();
        return json_decode($body, true);
    }

    /**
     * @param $objectName
     * @param $siteId
     * @param $objectId
     * @param $params
     * @param $publish
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function update($objectName, $siteId, $objectId, $params = [], $publish = 1): array
    {
        $response = $this->client->request('put', "sites/{$siteId}/{$objectName}/{$objectId}", [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'X-MT-Authorization' =>' MTAuth accessToken=' . $this->accessToken
            ],
            'form_params' => [
                'entry' => json_encode($params),
                'publish' => $publish
            ],
            'debug' => true
        ]);
        $body = $response->getBody();
        return json_decode($body, true);
    }

    /**
     * @param $objectName
     * @param $siteId
     * @param $objectId
     * @param $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delete($objectName, $siteId, $objectId, $params = []): array
    {
        $response = $this->client->request('delete', "sites/{$siteId}/{$objectName}/{$objectId}", [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'X-MT-Authorization' =>' MTAuth accessToken=' . $this->accessToken
            ],
            'debug' => true
        ]);
        $body = $response->getBody();
        return json_decode($body, true);
    }

    /**
     * @param $siteId
     * @param $templateId
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function publish($siteId, $templateId): array
    {
        $response = $this->client->request('post', "sites/{$siteId}/templates/{$templateId}/publish", [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'X-MT-Authorization' =>' MTAuth accessToken=' . $this->accessToken
            ],
            'debug' => true
        ]);
        $body = $response->getBody();
        return json_decode($body, true);
    }

    /**
     * @param $siteId
     * @param $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function listEntries($siteId, $params = null): array
    {
        return $this->list('entries', $siteId, $params);
    }

    /**
     * @param $siteId
     * @param $entryId
     * @param $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getEntry($siteId, $entryId, $params = null): array
    {
        return $this->get('entries', $siteId, $entryId, $params);
    }

    /**
     * @param $siteId
     * @param $params
     * @param $publish
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createEntry($siteId, $params = null, $publish = 1): array
    {
        return $this->create('entries', $siteId, $params, $publish);
    }

    /**
     * @param $siteId
     * @param $entryId
     * @param $params
     * @param $publish
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateEntry($siteId, $entryId, $params = null, $publish = 1): array
    {
        return $this->update('entries', $siteId, $entryId, $params, $publish);
    }

    /**
     * @param $siteId
     * @param $entryId
     * @param $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deleteEntry($siteId, $entryId, $params = null): array
    {
        return $this->delete('entries', $siteId, $entryId, $params);
    }
}