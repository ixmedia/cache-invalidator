<?php
require_once __DIR__ . '/CacheManager.php';
interface CacheInvalidationConfigParser {
    public function __construct(array $config);
    public function parseConfig();
}
