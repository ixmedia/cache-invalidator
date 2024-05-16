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
            echo "WP Cache cleared for home page with ID: " . $cacheKey . PHP_EOL;
        } else {
            echo "WP Cache not active or no compatible cache clear function available." . PHP_EOL;
        }
    }
}
