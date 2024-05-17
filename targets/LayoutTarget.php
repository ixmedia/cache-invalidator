<?php
require_once __DIR__ . '/../interfaces/CacheInvalidationTarget.php';
require_once __DIR__ . '/../interfaces/CacheManager.php';

class LayoutTarget implements CacheInvalidationTarget {
    private CacheManager $cacheManager;

    public function __construct(CacheManager $cacheManager) {
        $this->cacheManager = $cacheManager;
    }


    public function getTargetId(): string {
        return "layout";
    }

    public function invalidate(): void {
        $this->cacheManager->invalidateAllCache();
    }

}
