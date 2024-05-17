<?php

interface CacheManager {
    /**
     * Invalidates cache for the given cache key.
     * @param string $cacheKey The cache key to invalidate.
     * @return void
     */
    public function invalidateCache(string $cacheKey): void;
    public function invalidateAllCache(): void;
}
