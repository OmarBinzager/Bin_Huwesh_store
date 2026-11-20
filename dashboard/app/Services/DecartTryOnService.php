<?php

namespace App\Services;

use App\Model\BusinessSetting;
use App\Model\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Encapsulates Decart AI (Anywear / Lucy VTON) configuration, policy metadata,
 * and server-side ephemeral client token provisioning.
 *
 * The permanent DECART_API_KEY never leaves the server; clients receive only
 * short-lived, session-duration-constrained client tokens.
 */
class DecartTryOnService
{
    public const DEFAULT_MODEL = 'lucy-vton-latest';
    public const ALLOWED_MODELS = [
        'lucy-vton-latest',
        'lucy-vton-3.6',
        'lucy-vton-3.5',
    ];
    public const DEFAULT_SPEED = 'standard';
    public const DEFAULT_MAX_SECONDS = 45;
    public const TOKEN_EXPIRES_IN = 120;
    private const DECART_TOKENS_URL = 'https://api.decart.ai/v1/client/tokens';
    private const SETTINGS_CACHE_KEY = 'decart_try_on_settings_v1';

    /**
     * Read Try-On configuration from BusinessSetting (`storefront_settings.try_on`)
     * with environment variable fallback for the API key.
     */
    public function getSettings(): array
    {
        return Cache::remember(self::SETTINGS_CACHE_KEY, 300, function () {
            $raw = BusinessSetting::where('key', 'storefront_settings')->value('value');
            $sf = is_string($raw) ? (json_decode($raw, true) ?: []) : [];
            $cfg = is_array($sf['try_on'] ?? null) ? $sf['try_on'] : [];

            $apiKey = trim((string) ($cfg['api_key'] ?? ''));
            if ($apiKey === '') {
                $apiKey = trim((string) env('DECART_API_KEY', ''));
            }

            $model = (string) ($cfg['model'] ?? self::DEFAULT_MODEL);
            if (!in_array($model, self::ALLOWED_MODELS, true)) {
                $model = self::DEFAULT_MODEL;
            }

            $speed = (string) ($cfg['speed'] ?? self::DEFAULT_SPEED);
            if (!in_array($speed, ['standard', 'fast'], true)) {
                $speed = self::DEFAULT_SPEED;
            }

            $maxSeconds = (int) ($cfg['max_session_seconds'] ?? self::DEFAULT_MAX_SECONDS);
            $maxSeconds = max(15, min(180, $maxSeconds));

            $allowedOrigins = $cfg['allowed_origins'] ?? [];
            if (is_string($allowedOrigins)) {
                $allowedOrigins = array_values(array_filter(array_map('trim', explode(',', $allowedOrigins))));
            }

            return [
                'enabled'             => (bool) ($cfg['enabled'] ?? false),
                'api_key'             => $apiKey,
                'model'               => $model,
                'speed'               => $speed,
                'max_session_seconds' => $maxSeconds,
                'allowed_origins'     => is_array($allowedOrigins) ? $allowedOrigins : [],
            ];
        });
    }

    public static function clearCache(): void
    {
        Cache::forget(self::SETTINGS_CACHE_KEY);
    }

    /**
     * Public-safe configuration exposed to storefront & mobile clients.
     */
    public function getPublicConfig(string $locale = 'ar'): array
    {
        $settings = $this->getSettings();
        $isAvailable = $settings['enabled'] && $settings['api_key'] !== '';

        return [
            'enabled'             => $isAvailable,
            'model'               => $settings['model'],
            'speed'               => $settings['speed'],
            'max_session_seconds' => $settings['max_session_seconds'],
            'disclaimer'          => $locale === 'en'
                ? 'Approximate visual preview only, not a size recommendation.'
                : 'معاينة تقريبية للشكل، وليست توصية بالمقاس.',
            'privacy_notice'      => $locale === 'en'
                ? 'Your camera stream is sent encrypted in real time to Decart AI solely to preview the garment and stops immediately when you close this window.'
                : 'يُبث فيديو الكاميرا مشفّراً في الوقت الفعلي إلى خدمة Decart لمعاينة القطعة فقط، ويتوقف البث والكاميرا فور إغلاق هذه النافذة.',
        ];
    }

    /**
     * Check whether a product can be tried on right now.
     */
    public function isProductEligible(Product $product): bool
    {
        $settings = $this->getSettings();
        return $settings['enabled']
            && $settings['api_key'] !== ''
            && (int) $product->status === 1
            && (bool) $product->try_on_enabled;
    }

    /**
     * Provision a short-lived ephemeral client token from Decart AI.
     *
     * @throws RuntimeException when disabled, unconfigured, or Decart API fails
     */
    public function createClientToken(Product $product, ?string $requestOrigin = null): array
    {
        $settings = $this->getSettings();

        if (!$settings['enabled'] || $settings['api_key'] === '') {
            throw new RuntimeException('Virtual try-on service is currently disabled.');
        }

        if (!(bool) $product->try_on_enabled || (int) $product->status !== 1) {
            throw new RuntimeException('Virtual try-on is not enabled for this product.');
        }

        $payload = [
            'expiresIn'     => self::TOKEN_EXPIRES_IN,
            'allowedModels' => [$settings['model']],
            'constraints'   => [
                'realtime' => [
                    'maxSessionDuration' => $settings['max_session_seconds'],
                ],
            ],
        ];

        $origins = $settings['allowed_origins'];
        if (!empty($origins)) {
            $payload['allowedOrigins'] = $origins;
        } elseif ($requestOrigin && str_starts_with($requestOrigin, 'https://')) {
            // Optional origin scoping only when explicitly configured
        }

        $response = Http::timeout(12)
            ->withToken($settings['api_key'])
            ->acceptJson()
            ->post(self::DECART_TOKENS_URL, $payload);

        if (!$response->successful()) {
            Log::warning('Decart client token creation failed', [
                'status'     => $response->status(),
                'body'       => $response->body(),
                'product_id' => $product->id,
            ]);
            throw new RuntimeException('Unable to initialize try-on session right now.');
        }

        $data = $response->json() ?? [];
        $clientApiKey = $data['apiKey'] ?? $data['api_key'] ?? null;

        if (!$clientApiKey) {
            throw new RuntimeException('Invalid token response from try-on provider.');
        }

        return [
            'api_key'             => $clientApiKey,
            'expires_at'          => $data['expiresAt'] ?? $data['expires_at'] ?? null,
            'model'               => $settings['model'],
            'speed'               => $settings['speed'],
            'max_session_seconds' => $settings['max_session_seconds'],
        ];
    }
}
