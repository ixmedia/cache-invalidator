<?php

interface CacheManager {
    /**
     * Invalidates cache for the given cache key.
     * @param string $cacheKey The cache key to invalidate.
     * @return void
     */
    public function invalidateCache(string $cacheKey): void;
    public function invalidateAllCache(): void;

    /**
     * Invalidates cache for a specific front-end URL.
     * @param string $url The URL to invalidate.
     * @return void
     */
    public function invalidateUrl(string $url): void;
}