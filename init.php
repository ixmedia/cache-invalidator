<?php
/*
 * Plugin Name: Cache Invalidator
 * Description: A powerful plugin for managing cache invalidation across various sections of your WordPress site. This plugin allows you to define triggers and targets for cache invalidation, ensuring your site's content is always up-to-date.
 * Author: Sébastien Asselin
 * Version: 1.0.0
 */
require_once __DIR__ . '/parser/PhpFileConfigParser.php';
require_once __DIR__ . '/CacheInvalidator.php';

// Initialize Cache Invalidator
function initialize_cache_invalidator() {
  global $cacheInvalidator;

  $config = require __DIR__ . '/config.php';
  $configParser = new PhpFileConfigParser($config);
  $cacheInvalidator = new CacheInvalidator($configParser);
}

add_action('plugins_loaded', 'initialize_cache_invalidator');

// Optionally, load admin-specific functions or pages
if (is_admin()) {
  require_once __DIR__ . '/experimental/CacheInvalidatorAdmin.php';
  new CacheInvalidatorAdmin();
}
