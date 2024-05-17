<?php
/*
* Plugin Name: Cache Invalidator
* Description: Cache Invalidator
* Author: Sébastien Asselin
* Version: 1.0.0
*/
$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/CacheInvalidationManager.php';
$cacheInvalidationManager = new CacheInvalidationManager($config);


require_once __DIR__ . '/experimental/admin.php';



