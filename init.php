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

/**
 * Queue a Gutenberg block for cache invalidation.
 *
 * @param string $blockName Gutenberg block name, e.g. ix/block-related-projects.
 * @param string|null $invalidationDate MySQL datetime or null for now.
 * @param bool $onlyIfNotExists Skip queueing if an entry for this block is already pending.
 */
function cache_invalidator_queue_gutenberg_block(string $blockName, ?string $invalidationDate = null, bool $onlyIfNotExists = false): void {
    if ($blockName === '') {
        return;
    }

    $queueType = 'gutenberg:' . $blockName;
    $queueDate = $invalidationDate ?: current_time('mysql');

    if ($onlyIfNotExists) {
        CacheInvalidationHelper::insertIntoQueueIfNotExists(0, $queueType, 'GutenbergQueueTrigger', [$queueDate]);
    } else {
        CacheInvalidationHelper::insertIntoQueue(0, $queueType, 'GutenbergQueueTrigger', [$queueDate]);
    }
}

/**
 * Queue all Gutenberg blocks configured in the plugin admin for cache invalidation.
 *
 * @param string|null $invalidationDate MySQL datetime or null for now.
 * @param bool $onlyIfNotExists Skip queueing a block if an entry is already pending.
 */
function cache_invalidator_queue_configured_gutenberg_blocks(?string $invalidationDate = null, bool $onlyIfNotExists = false): void {
    $config = get_cache_invalidator_config();
    $cronConfig = isset($config['cron']) && is_array($config['cron']) ? $config['cron'] : [];
    $gutenbergBlocks = isset($cronConfig['gutenbergBlocks']) && is_array($cronConfig['gutenbergBlocks'])
        ? array_values(array_filter($cronConfig['gutenbergBlocks'], static function ($blockName) {
            return is_string($blockName) && trim($blockName) !== '';
        }))
        : [];

    foreach ($gutenbergBlocks as $blockName) {
        cache_invalidator_queue_gutenberg_block($blockName, $invalidationDate, $onlyIfNotExists);
    }
}

/**
 * Get the configured Gutenberg flush frequency, in hours (minimum 1).
 */
function cache_invalidator_get_gutenberg_interval_hours(): int {
    $config = get_cache_invalidator_config();
    $cronConfig = isset($config['cron']) && is_array($config['cron']) ? $config['cron'] : [];
    $intervalHours = isset($cronConfig['intervalHours']) ? (int) $cronConfig['intervalHours'] : 6;

    return max(1, $intervalHours);
}

/**
 * Runs on WP-Cron: processes any due cache invalidation queue entries, then re-queues
 * the configured Gutenberg blocks for the next flush cycle.
 */
function cache_invalidator_run_cron_queue_processing(): void {
    global $cacheInvalidator;

    if ($cacheInvalidator instanceof CacheInvalidator) {
        $cacheInvalidator->processQueue();
    }

    $intervalHours = cache_invalidator_get_gutenberg_interval_hours();
    cache_invalidator_queue_configured_gutenberg_blocks(
        date('Y-m-d H:i:s', current_time('timestamp') + ($intervalHours * HOUR_IN_SECONDS)),
        true
    );
}
add_action('cache_invalidator_process_queue', 'cache_invalidator_run_cron_queue_processing');

/**
 * Single source of truth for the WP-Cron recurrence name used to check the
 * cache invalidation queue. Change this value (and its definition below) to
 * change the queue-processing frequency; cache_invalidator_ensure_cron_scheduled()
 * will detect the change and reschedule the event under the new name.
 */
function cache_invalidator_get_cron_schedule_name(): string {
    return 'cache_invalidator_every_minute';
}

/**
 * Register the WP-Cron recurrence used to check the cache invalidation queue.
 */
function cache_invalidator_register_cron_schedule(array $schedules): array {
    $schedules[cache_invalidator_get_cron_schedule_name()] = [
        'interval' => MINUTE_IN_SECONDS,
        'display'  => __('Every minute (Cache Invalidator)', 'cache_invalidator'),
    ];

    return $schedules;
}
add_filter('cron_schedules', 'cache_invalidator_register_cron_schedule');

/**
 * Make sure the recurring event is scheduled under the current schedule name,
 * in case activation was skipped (e.g. the plugin was already active before
 * this feature was deployed) or the schedule name/interval was changed since
 * the event was last scheduled.
 */
function cache_invalidator_ensure_cron_scheduled(): void {
    $scheduleName = cache_invalidator_get_cron_schedule_name();
    $event = wp_get_scheduled_event('cache_invalidator_process_queue');

    if ($event && $event->schedule !== $scheduleName) {
        wp_clear_scheduled_hook('cache_invalidator_process_queue');
        $event = null;
    }

    if (!$event) {
        wp_schedule_event(time(), $scheduleName, 'cache_invalidator_process_queue');
    }
}
add_action('plugins_loaded', 'cache_invalidator_ensure_cron_scheduled');

function cache_invalidator_activate(): void {
    cache_invalidator_ensure_cron_scheduled();
}
register_activation_hook(__FILE__, 'cache_invalidator_activate');

function cache_invalidator_deactivate(): void {
    wp_clear_scheduled_hook('cache_invalidator_process_queue');
}
register_deactivation_hook(__FILE__, 'cache_invalidator_deactivate');

// Optionally, load admin-specific functions or pages
if (is_admin()) {
    require_once __DIR__ . '/admin/CacheInvalidatorAdmin.php';
    new CacheInvalidatorAdmin();
}
