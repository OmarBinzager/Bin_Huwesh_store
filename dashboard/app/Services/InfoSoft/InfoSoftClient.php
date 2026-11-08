<?php

namespace App\Services\InfoSoft;

use App\Model\InfoSoftSetting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class InfoSoftClient
{
    protected InfoSoftSetting $settings;
    protected InfoSoftTokenManager $tokenManager;
    protected Client $httpClient;

    public function __construct(InfoSoftSetting $settings = null, InfoSoftTokenManager $tokenManager = null)
    {
        $this->settings = $settings ?? InfoSoftSetting::getActive();
        $this->tokenManager = $tokenManager ?? new InfoSoftTokenManager($this->settings);

        $this->httpClient = new Client([
            'base_uri' => rtrim($this->settings->api_base_url ?? '', '/') . '/',
            'timeout'  => 30.0,
            'verify'   => false,
        ]);
    }

    public function getSettings(): InfoSoftSetting
    {
        return $this->settings;
    }

    public function getTokenManager(): InfoSoftTokenManager
    {
        return $this->tokenManager;
    }

    /**
     * Send GET request with JWT Token & automatic 401 retry
     */
    public function get(string $endpoint, array $queryParams = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $queryParams]);
    }

    /**
     * Send POST request with JWT Token & automatic 401 retry
     */
    public function post(string $endpoint, array $payload = [], array $queryParams = []): array
    {
        $options = [];
        if (!empty($payload)) {
            $options['json'] = $payload;
        }
        if (!empty($queryParams)) {
            $options['query'] = $queryParams;
        }

        return $this->request('POST', $endpoint, $options);
    }

    /**
     * Core request handler
     */
    protected function request(string $method, string $endpoint, array $options = [], bool $isRetry = false): array
    {
        $token = $this->tokenManager->getToken();

        if (empty($token)) {
            Log::error("[InfoSoftClient] Unable to obtain Bearer token for {$method} {$endpoint}");
            return [
                'isSuccess' => false,
                'status'    => false,
                'message'   => 'ERP Authentication failed. No valid token available.',
            ];
        }

        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];

        $options['headers'] = array_merge($options['headers'] ?? [], $headers);

        try {
            $response = $this->httpClient->request($method, ltrim($endpoint, '/'), $options);
            $body = (string)$response->getBody();
            $decoded = json_decode($body, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }

            return [
                'isSuccess' => true,
                'raw'       => $body,
            ];
        } catch (ClientException $e) {
            // Handle HTTP 401 Unauthorized by clearing cache and retrying once
            if ($e->getResponse() && $e->getResponse()->getStatusCode() === 401 && !$isRetry) {
                Log::warning("[InfoSoftClient] 401 Unauthorized received. Clearing token cache and retrying...");
                $this->tokenManager->clearToken();
                return $this->request($method, $endpoint, $options, true);
            }

            $responseBody = $e->hasResponse() ? (string)$e->getResponse()->getBody() : '';
            Log::error("[InfoSoftClient] Client error ({$e->getCode()}) on {$method} {$endpoint}: {$responseBody}");

            $decoded = json_decode($responseBody, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            return [
                'isSuccess' => false,
                'status'    => false,
                'message'   => "Client Error {$e->getCode()}: " . substr($responseBody, 0, 200),
            ];
        } catch (RequestException $e) {
            $errMsg = $e->getMessage();
            Log::error("[InfoSoftClient] Request exception on {$method} {$endpoint}: {$errMsg}");

            return [
                'isSuccess' => false,
                'status'    => false,
                'message'   => "Network/ERP Connection Error: {$errMsg}",
            ];
        } catch (\Throwable $e) {
            Log::error("[InfoSoftClient] General error on {$method} {$endpoint}: " . $e->getMessage());

            return [
                'isSuccess' => false,
                'status'    => false,
                'message'   => $e->getMessage(),
            ];
        }
    }
}
