<?php

namespace bitpart\dataapi;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;

class DataAPI
{
    private string $clientId = 'php-client';
    private Client $client;

    private string $username = '';
    private string $password = '';
    private string $baseUrl = '';

    private string $accessToken = '';
    private string $expiresIn = '';
    private string $remember = '';
    private string $sessionId = '';
    private string $debug = '';

    /**
     * @param string $username
     * @param string $password
     * @param string $baseUrl
     * @param array $options
     * @param bool $debug
     */
    public function __construct(string $username, string $password, string $baseUrl, array $options = [], bool $debug = false)
    {
        $this->username = $username;
        $this->password = $password;
        $this->baseUrl = $baseUrl;
        $this->debug = $debug;

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
     * @param ClientException $e
     * @return array
     */
    private function error(ClientException $e): array
    {
        return ['error' => true, 'message' => Psr7\Message::toString($e->getResponse())];
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
                ],
                'debug' => $this->debug,
            ]);
            $body = $response->getBody();
            $body = json_decode($body, true);
            $this->accessToken = $body['accessToken'];
            $this->expiresIn = $body['expiresIn'];
            $this->remember = $body['remember'];
            $this->sessionId = $body['sessionId'];
        } catch (ClientException $e) {
            echo Psr7\Message::toString($e->getResponse());
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
            ],
            'debug' => $this->debug,
        ]);
        $body = $response->getBody();
        return json_decode($body, true);
    }

    /**
     * @param string $path
     * @param array $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function list(string $path, array $params = []): array
    {
        $query = count($params) ? [
            'query' => $params
        ] : [];
        if ($params && isset($params['status']) && strpos($params['status'], 'Draft') !== false) {
            $query['headers'] = [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'X-MT-Authorization' =>' MTAuth accessToken=' . $this->accessToken
            ];
        }
        try {
            $response = $this->client->request('get', $path, $query);
            $body = $response->getBody();
            return json_decode($body, true);
        } catch (ClientException $e) {
            return $this->error($e);
        }
    }

    /**
     * @param array $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function search(array $params): array
    {
        $query = count($params) ? [
            'query' => $params
        ] : [];
        try {
            $response = $this->client->request('get', 'search', $query);
            $body = $response->getBody();
            return json_decode($body, true);
        } catch (ClientException $e) {
            return $this->error($e);
        }
    }

    /**
     * @param string $path
     * @param array $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get(string $path, array $params = []): array
    {
        $query = count($params) ? [
            'query' => $params
        ] : [];
        try {
            $response = $this->client->request('get', $path, $query);
            $body = $response->getBody();
            return json_decode($body, true);
        } catch (ClientException $e) {
            return $this->error($e);
        }

    }

    /**
     * @param string $objectType entry|content_data
     * @param string $path
     * @param array $params
     * @param int $publish
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function create(string $objectType, string $path, array $params, int $publish = 0): array
    {
        try {
            $response = $this->client->request('post', $path, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'X-MT-Authorization' =>' MTAuth accessToken=' . $this->accessToken
                ],
                'form_params' => [
                    ($objectType) => json_encode($params),
                    'publish' => $publish
                ],
                'debug' => $this->debug,
            ]);
            $body = $response->getBody();
            return json_decode($body, true);
        } catch (ClientException $e) {
            return $this->error($e);
        }
    }

    /**
     * @param string $objectType
     * @param string $path
     * @param array $params
     * @param int $publish
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function update(string $objectType, string $path, array $params = [], int $publish = 1): array
    {
        try {
            $response = $this->client->request('put', $path, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'X-MT-Authorization' =>' MTAuth accessToken=' . $this->accessToken
                ],
                'form_params' => [
                    ($objectType) => json_encode($params),
                    'publish' => $publish
                ],
                'debug' => $this->debug,
            ]);
            $body = $response->getBody();
            return json_decode($body, true);
        } catch (ClientException $e) {
            return $this->error($e);
        }
    }

    /**
     * @param string $path
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delete(string $path): array
    {
        try {
            $response = $this->client->request('delete', $path, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'X-MT-Authorization' =>' MTAuth accessToken=' . $this->accessToken
                ],
                'debug' => $this->debug,
            ]);
            $body = $response->getBody();
            return json_decode($body, true);
        } catch (ClientException $e) {
            return $this->error($e);
        }

    }

    /**
     * @param int $siteId
     * @param int $templateId
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function publish(int $siteId, int $templateId): array
    {
        try {
            $response = $this->client->request('post', "sites/{$siteId}/templates/{$templateId}/publish", [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'X-MT-Authorization' =>' MTAuth accessToken=' . $this->accessToken
                ],
                'debug' => $this->debug,
            ]);
            $body = $response->getBody();
            return json_decode($body, true);
        } catch (ClientException $e) {
            return $this->error($e);
        }
    }

    //----------------------------------------------------------------------
    // Entry
    //----------------------------------------------------------------------
    /**
     * @param string $siteId
     * @param array $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function listEntries(string $siteId, array $params = []): array
    {
        return $this->list("sites/{$siteId}/entries", $params);
    }

    /**
     * @param int $siteId
     * @param int $entryId
     * @param array|null $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getEntry(int $siteId, int $entryId, array $params = []): array
    {
        return $this->get("sites/{$siteId}/entries/{$entryId}", $params);
    }

    /**
     * @param int $siteId
     * @param array $params
     * @param int $publish
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createEntry(int $siteId, array $params = [], int $publish = 1): array
    {
        return $this->create('entry', "sites/{$siteId}/entries", $params, $publish);
    }

    /**
     * @param int $siteId
     * @param int $entryId
     * @param array $params
     * @param int $publish
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateEntry(int $siteId, int $entryId, array $params = [], int $publish = 1): array
    {
        return $this->update('entry', "sites/{$siteId}/entries/{$entryId}", $params, $publish);
    }

    /**
     * @param int $siteId
     * @param int $entryId
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deleteEntry(int $siteId, int $entryId): array
    {
        return $this->delete("sites/{$siteId}/entries/{$entryId}");
    }

    //----------------------------------------------------------------------
    // Content Data
    //----------------------------------------------------------------------
    /**
     * @param int $siteId
     * @param int $contentTypeId
     * @param array $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function listContentData(int $siteId, int $contentTypeId, array $params = []): array
    {
        $path = "sites/{$siteId}/contentTypes/{$contentTypeId}/data";
        return $this->list($path, $params);
    }

    /**
     * @param int $siteId
     * @param int $contentTypeId
     * @param int $contentDataId
     * @param string $fields
     * @param array|null $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getContentData(int $siteId, int $contentTypeId, int $contentDataId, string $fields = '', array $params = []): array
    {
        return $this->get("sites/{$siteId}/contentTypes/{$contentTypeId}/data/{$contentDataId}{$fields}", $params);
    }

    /**
     * @param int $siteId
     * @param int $contentTypeId
     * @param array $params
     * @param int $publish
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createContentData(int $siteId, int $contentTypeId, array $params, int $publish = 1): array
    {
        return $this->create('content_data', "sites/{$siteId}/contentTypes/{$contentTypeId}/data", $params, $publish);
    }

    /**
     * @param int $siteId
     * @param int $contentTypeId
     * @param array $params
     * @param int $publish
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateContentData(int $siteId, int $contentTypeId, int $contentDataId, array $params, int $publish = 1): array
    {
        return $this->create('content_data', "sites/{$siteId}/contentTypes/{$contentTypeId}/data/{$contentDataId}", $params, $publish);
    }

    /**
     * @param int $siteId
     * @param int $contentTypeId
     * @param array $params
     * @param int $publish
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deleteContentData(int $siteId, int $contentTypeId, int $contentDataId): array
    {
        return $this->delete("sites/{$siteId}/contentTypes/{$contentTypeId}/data/{$contentDataId}");
    }

    //----------------------------------------------------------------------
    // File Upload
    //----------------------------------------------------------------------
    /**
     * ファイルをアップロードします。
     *
     * クエリパラメータ:
     *  - overwrite_once (integer: 0|1)
     *
     * multipart/form-data の各パラメータ:
     *  - autoRenameIfExists (integer: 0|1, default: 0)
     *  - autoRenameNonAscii (integer: 0|1)
     *  - file (binary) 実際のファイルデータ。ファイルパスを指定した場合は fopen() で読み込みます。
     *  - normalizeOrientation (integer: 0|1, default: 1)
     *  - path (string) サイト内のアップロード先パス
     *  - site_id (integer) サイトID
     *
     * @param array $params アップロードに必要なパラメータ群
     * @param int $overwrite_once クエリパラメータ overwrite_once (0 または 1)
     * @return array API レスポンス
     * @throws GuzzleException
     */
    public function uploadFile(array $params, int $overwrite_once = 0): array
    {
        if (!isset($params['site_id'])) {
            return ['error' => true, 'message' => 'site_id is required'];
        }
        if (!isset($params['file'])) {
            return ['error' => true, 'message' => 'file is required'];
        }
        $siteId = $params['site_id'];

        // クエリパラメータ
        $query = [];
        if ($overwrite_once === 1) {
            $query['overwrite_once'] = 1;
        }

        // multipart 用データの構築
        $multipart = [];
        $allowedFields = ['autoRenameIfExists', 'autoRenameNonAscii', 'normalizeOrientation', 'path', 'site_id'];
        foreach ($allowedFields as $field) {
            if (isset($params[$field])) {
                $multipart[] = [
                    'name' => $field,
                    'contents' => $params[$field]
                ];
            }
        }

        // file パラメータの処理（fopenでファイルリソースを取得）
        $fileValue = $params['file'];
        if (is_string($fileValue) && file_exists($fileValue)) {
            $handle = fopen($fileValue, 'r');  // ファイルリソースを取得
            $multipart[] = [
                'name' => 'file',
                'contents' => $handle,
                'filename' => basename($fileValue)
            ];
        } else {
            $multipart[] = [
                'name' => 'file',
                'contents' => $fileValue
            ];
        }

        try {
            $response = $this->client->request('post', "assets/upload", [
                'headers' => [
                    'X-MT-Authorization' => 'MTAuth accessToken=' . $this->accessToken,
                ],
                'query' => $query,
                'multipart' => $multipart,
                'debug' => $this->debug,
            ]);
            $body = $response->getBody();
            $result = json_decode($body, true);

            // リクエスト後、開いたファイルリソースを明示的に閉じる
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            return $result;
        } catch (ClientException $e) {
            // 例外発生時もリソースが開いていれば閉じる
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            return $this->error($e);
        }
    }
}
