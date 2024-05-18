<?php

require_once __DIR__ . '/../interfaces/CacheManager.php';

class WPManager implements CacheManager {
    /**
     * Invalidates cache for the given cache key.
     * @param string $cacheKey The cache key to invalidate.
     * @return void
     */
    public function invalidateCache(string $cacheKey): void {
        if (function_exists('wp_cache_delete')) {
            // Clear cache using W3 Total Cache
            wp_cache_delete($cacheKey, 'page');
            trigger_error("WP Cache: cleared for key: " . $cacheKey, E_USER_NOTICE);
        } else {
            trigger_error("WP Cache not active or no compatible cache clear function available.", E_ERROR);
        }
    }

    /**
     * Invalidates all caches.
     * @return void
     */
    public function invalidateAllCache(): void {
        if (function_exists('wp_cache_flush')) {
            // Clear all cache using W3 Total Cache
            wp_cache_flush();
            trigger_error("WP: All Cache cleared.", E_USER_NOTICE);
        } else {
            trigger_error("WP Total Cache not active or no compatible cache flush function available.", E_ERROR);
        }
    }
}
