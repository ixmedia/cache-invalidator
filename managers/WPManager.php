<?php
require_once __DIR__ . '/BaseCacheManager.php';

class WPManager extends BaseCacheManager {
    public function __construct() {
        parent::__construct("WP Cache");
    }

    protected function getClearCacheFunction(): string {
        return 'wp_cache_delete';
    }

    protected function getClearAllCacheFunction(): string {
        return 'wp_cache_flush';
    }

    protected function getFlushUrlFunction(): ?string {
        // The WP object cache is not addressable by URL.
        return null;
    }
}
