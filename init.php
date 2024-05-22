<?php
/*
 * Plugin Name: Cache Invalidator
 * Description: A powerful plugin for managing cache invalidation across various sections of your WordPress site. This plugin allows you to define triggers and targets for cache invalidation, ensuring your site's content is always up-to-date.
 * Author: Sébastien Asselin
 * Version: 1.0.0
 */
require_once __DIR__ . '/parser/PhpFileConfigParser.php';
require_once __DIR__ . '/CacheInvalidator.php';

// Function to get configuration from config.json
function get_cache_invalidator_config() {
    $themeDir = get_template_directory();
    $configFilePath = $themeDir . '/cache-invalidator/config.json';

    if (file_exists($configFilePath)) {
        $json = file_get_contents($configFilePath);
        return json_decode($json, true);
    }

    return [];
}

// Initialize Cache Invalidator
function initialize_cache_invalidator() {
    global $cacheInvalidator;

    $config = get_cache_invalidator_config();

    if (empty($config)) {
        $config = require __DIR__ . '/config.php';
    }

    //  error_log(json_encode($config), JSON_PRETTY_PRINT);

    $configParser = new PhpFileConfigParser($config);
    $cacheInvalidator = new CacheInvalidator($configParser);
}

add_action('plugins_loaded', 'initialize_cache_invalidator');

// Optionally, load admin-specific functions or pages
if (is_admin()) {
    require_once __DIR__ . '/admin/CacheInvalidatorAdmin.php';
    new CacheInvalidatorAdmin();
}
