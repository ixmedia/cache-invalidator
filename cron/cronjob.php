<?php
require_once dirname(__FILE__, 3) . '/../../wp-load.php';
$config = require __DIR__ . '/../config.php';
require_once __DIR__ . '/../CacheInvalidationManager.php';
require_once __DIR__ . '/CacheInvalidationProcessor.php';
$cacheInvalidationManager = new CacheInvalidationManager($config);
$cacheInvalidationProcessor = new CacheInvalidationProcessor($cacheInvalidationManager);
$cacheInvalidationProcessor->processInvalidation();
