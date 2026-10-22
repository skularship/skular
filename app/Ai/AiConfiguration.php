<?php

namespace App\Ai;

use App\Exceptions\AiConfigurationException;

/**
 * Reads and validates the configured AI provider and text model.
 *
 * The Laravel AI SDK still resolves the provider and model for every prompt;
 * this class only fails fast when the central configuration is missing so an
 * AI feature never silently runs against an unintended SDK fallback.
 */
final class AiConfiguration
{
    /**
     * Get the configured default provider name.
     *
     * @throws AiConfigurationException
     */
    public static function provider(): string
    {
        $provider = config('ai.default');

        if (! is_string($provider) || trim($provider) === '') {
            throw new AiConfigurationException('Provider AI default belum dikonfigurasi pada config/ai.php.');
        }

        return $provider;
    }

    /**
     * Get the configured default text model for a provider.
     *
     * @throws AiConfigurationException
     */
    public static function textModel(?string $provider = null): string
    {
        $provider ??= self::provider();

        $models = config('ai.providers.'.$provider.'.models');
        $model = is_array($models) ? ($models['text']['default'] ?? null) : null;

        if (! is_string($model) || trim($model) === '') {
            throw new AiConfigurationException("Model teks AI untuk provider [{$provider}] belum dikonfigurasi pada config/ai.php.");
        }

        return $model;
    }

    /**
     * Ensure the default text model is configured before prompting an agent.
     *
     * @throws AiConfigurationException
     */
    public static function ensureTextModelConfigured(): void
    {
        self::textModel();
    }
}
