<?php

require_once __DIR__ . '/../interfaces/CacheManager.php';

class W3TCManager implements CacheManager {
    /**
     * Invalidates cache for the given cache key.
     * @param string $cacheKey The cache key to invalidate.
     * @return void
     */
    public function invalidateCache(string $cacheKey): void {
        if (function_exists('w3tc_flush_post')) {
            // Clear cache using W3 Total Cache
            w3tc_flush_post($cacheKey);
            trigger_error("W3 Total Cache: cleared for key: " . $cacheKey, E_USER_NOTICE);
        } else {
            trigger_error("W3 Total Cache not active or no compatible cache clear function available.", E_ERROR);
        }
    }

    /**
     * Invalidates all caches.
     * @return void
     */
    public function invalidateAllCache(): void {
        if (function_exists('w3tc_flush_all')) {
            // Clear all cache using W3 Total Cache
            w3tc_flush_all();
            trigger_error("W3 Total Cache: All  cleared.", E_USER_NOTICE);
        } else {
            trigger_error("W3 Total Cache not active or no compatible cache flush function available." , E_ERROR);
        }
    }
}
