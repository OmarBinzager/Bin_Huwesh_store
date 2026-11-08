<?php

namespace App\Services\InfoSoft;

use App\Model\InfoSoftSetting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InfoSoftTokenManager
{
    const CACHE_KEY = 'infosoft_jwt_token';

    protected InfoSoftSetting $settings;
    protected Client $httpClient;

    public function __construct(InfoSoftSetting $settings = null)
    {
        $this->settings = $settings ?? InfoSoftSetting::getActive();
        $this->httpClient = new Client([
            'base_uri' => rtrim($this->settings->api_base_url, '/') . '/',
            'timeout'  => 15.0,
            'verify'   => false, // Allow self-signed or internal SSL
        ]);
    }

    /**
     * Get a valid token from cache or authenticate
     */
    public function getToken(bool $forceRefresh = false): ?string
    {
        if (!$forceRefresh && Cache::has(self::CACHE_KEY)) {
            return Cache::get(self::CACHE_KEY);
        }

        return $this->login();
    }

    /**
     * Clear cached token on 401 or manual reset
     */
    public function clearToken(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Authenticate against POST /api/Login
     */
    public function login(): ?string
    {
        $baseUrl = trim($this->settings->api_base_url ?? '');
        $username = trim($this->settings->username ?? '');
        $password = $this->settings->decrypted_password ?? '';

        if (empty($baseUrl) || empty($username) || empty($password)) {
            Log::warning('[InfoSoft] Missing credentials for login');
            return null;
        }

        try {
            $response = $this->httpClient->post('api/Login', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'json' => [
                    'username' => $username,
                    'password' => $password,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode((string)$response->getBody(), true);

            if ($statusCode === 200 && !empty($body['accessToken'])) {
                $token = $body['accessToken'];
                $expireSeconds = 86400 * 30; // 30 days default cache TTL

                if (!empty($body['expireIn']) && preg_match('/(\d+)/', $body['expireIn'], $matches)) {
                    // Use returned expiry or max 30 days
                    $expireSeconds = min((int)$matches[1], 86400 * 30);
                }

                Cache::put(self::CACHE_KEY, $token, now()->addSeconds($expireSeconds));

                if (!empty($body['database'])) {
                    $this->settings->update([
                        'database_name' => $body['database'],
                    ]);
                }

                return $token;
            }

            Log::error('[InfoSoft] Login failed: unexpected response', ['body' => $body]);
            return null;
        } catch (\Throwable $e) {
            Log::error('[InfoSoft] Login exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Live test connection for Admin Dashboard
     */
    public function testConnection(): array
    {
        $start = microtime(true);
        $baseUrl = trim($this->settings->api_base_url ?? '');
        $username = trim($this->settings->username ?? '');
        $password = $this->settings->decrypted_password ?? '';

        if (empty($baseUrl)) {
            return [
                'status'  => false,
                'message' => 'API Base URL is required',
                'latency' => 0,
            ];
        }

        if (empty($username) || empty($password)) {
            return [
                'status'  => false,
                'message' => 'Username and password are required',
                'latency' => 0,
            ];
        }

        try {
            $response = $this->httpClient->post('api/Login', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'json' => [
                    'username' => $username,
                    'password' => $password,
                ],
            ]);

            $latency = round((microtime(true) - $start) * 1000, 2);
            $body = json_decode((string)$response->getBody(), true);

            if (!empty($body['accessToken'])) {
                $dbName = $body['database'] ?? 'Unknown';
                $msg = "Connection successful. Connected database: {$dbName}. Latency: {$latency}ms";

                $this->settings->update([
                    'last_connection_test_at' => now(),
                    'last_connection_status'  => 'success',
                    'last_connection_message' => $msg,
                    'database_name'           => $dbName,
                ]);

                // Cache the newly received token
                Cache::put(self::CACHE_KEY, $body['accessToken'], now()->addDays(7));

                return [
                    'status'   => true,
                    'message'  => $msg,
                    'database' => $dbName,
                    'latency'  => $latency,
                    'expireIn' => $body['expireIn'] ?? 'N/A',
                ];
            }

            $errMsg = $body['message'] ?? 'Authentication failed. Please verify credentials.';
            $this->settings->update([
                'last_connection_test_at' => now(),
                'last_connection_status'  => 'failed',
                'last_connection_message' => $errMsg,
            ]);

            return [
                'status'  => false,
                'message' => $errMsg,
                'latency' => $latency,
            ];
        } catch (RequestException $e) {
            $latency = round((microtime(true) - $start) * 1000, 2);
            $errMsg = $e->hasResponse() ? (string)$e->getResponse()->getBody() : $e->getMessage();

            $this->settings->update([
                'last_connection_test_at' => now(),
                'last_connection_status'  => 'failed',
                'last_connection_message' => "HTTP Error: " . substr($errMsg, 0, 255),
            ]);

            return [
                'status'  => false,
                'message' => "Connection failed: " . substr($errMsg, 0, 255),
                'latency' => $latency,
            ];
        } catch (\Throwable $e) {
            $latency = round((microtime(true) - $start) * 1000, 2);
            $msg = $e->getMessage();

            $this->settings->update([
                'last_connection_test_at' => now(),
                'last_connection_status'  => 'failed',
                'last_connection_message' => $msg,
            ]);

            return [
                'status'  => false,
                'message' => "Connection error: {$msg}",
                'latency' => $latency,
            ];
        }
    }
}
