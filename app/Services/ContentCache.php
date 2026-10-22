<?php

namespace App\Services;

use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache namespace for public curriculum and catalog data.
 *
 * Content is read far more often than it is edited, so hot reads are cached
 * under a version key. Any curriculum or scholarship write bumps the version,
 * which invalidates every derived key at once without having to track each key
 * individually. Old entries simply expire through their TTL.
 *
 * `remember()` uses a stale-while-revalidate window and a short lock, so a cold
 * key after an edit does not stampede the database with identical queries.
 *
 * Only scalars and arrays may be cached. Laravel's cache stores refuse to
 * unserialize PHP objects by default (`serializable_classes`), so cached
 * Eloquent models would come back as an incomplete class; callers cache the
 * raw attributes and rebuild the models instead.
 */
final class ContentCache
{
    /**
     * Cache key holding the current content version.
     */
    private const VERSION_KEY = 'content:version';

    /**
     * Seconds a cached public collection stays valid before it is dropped.
     */
    public const TTL_SECONDS = 3600;

    /**
     * Seconds before a cached value is refreshed in the background.
     */
    public const FRESH_SECONDS = 300;

    /**
     * Seconds the refresh lock is held to avoid concurrent recomputation.
     */
    private const LOCK_SECONDS = 10;

    /**
     * Read a public dataset through the versioned, stampede-protected cache.
     *
     * @template TValue
     *
     * @param  callable(): TValue  $callback
     * @return TValue
     */
    public static function remember(string $namespace, callable $callback): mixed
    {
        $key = self::key($namespace);

        $value = Cache::flexible(
            $key,
            [self::FRESH_SECONDS, self::TTL_SECONDS],
            $callback,
            ['seconds' => self::LOCK_SECONDS],
        );

        // Entries written before the cache was limited to scalar data can hold
        // a serialized model or collection. Those are returned as an incomplete
        // class, so drop both flexible keys and recompute so the fresh value is
        // cached again instead of being recomputed on every request.
        if ($value instanceof \__PHP_Incomplete_Class) {
            Cache::forget($key);
            Cache::forget(Repository::FLEXIBLE_CREATED_KEY_PREFIX.$key);

            return self::remember($namespace, $callback);
        }

        return $value;
    }

    /**
     * Get the cache key for a public dataset.
     */
    public static function key(string $namespace): string
    {
        return 'content:'.self::version().':'.$namespace;
    }

    /**
     * Get the current content version.
     */
    public static function version(): int
    {
        $version = Cache::get(self::VERSION_KEY, 1);

        return is_numeric($version) ? (int) $version : 1;
    }

    /**
     * Invalidate every cached public dataset.
     */
    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }
}
