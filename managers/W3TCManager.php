<?php
require_once __DIR__ . '/BaseCacheManager.php';

class W3TCManager extends BaseCacheManager {
    public function __construct() {
        parent::__construct("W3TC");
    }

    protected function getClearCacheFunction(): string {
        return 'w3tc_flush_post';
    }

    protected function getClearAllCacheFunction(): string {
        return 'w3tc_flush_all';
    }

    protected function getFlushUrlFunction(): ?string {
        return 'w3tc_flush_url';
    }
}
