<?php
// cron/cronjob.php
// @@TODO Reconstruire les classes nécessaire et exister les invalidations nécessaire. Tout est à refaire ici

function check_cache_invalidation_queue_function() {
    global $wpdb;
    $table = $wpdb->prefix . 'cache_invalidation_queue';
    $currentDate = new DateTime();

    // Fetch rows that need to be checked
    $rows = $wpdb->get_results("SELECT * FROM $table WHERE checked_at IS NULL OR checked_at <= NOW()");

    foreach ($rows as $row) {
        $fieldDate = new DateTime($row->field_value);
        $invalidate = false;

        switch ($row->comparator) {
            case '<=':
                $invalidate = $fieldDate <= $currentDate;
                break;
            case '>=':
                $invalidate = $fieldDate >= $currentDate;
                break;
            case '<':
                $invalidate = $fieldDate < $currentDate;
                break;
            case '>':
                $invalidate = $fieldDate > $currentDate;
                break;
        }

        if ($invalidate) {
            // Invalidate the cache
            echo "Invalidating cache for " . $row->target_description . PHP_EOL;

            // Mark the row as checked
            $wpdb->update(
                $table,
                ['checked_at' => current_time('mysql')],
                ['id' => $row->id]
            );
        }
    }
}

if (!wp_next_scheduled('check_cache_invalidation_queue')) {
    wp_schedule_event(time(), 'minute', 'check_cache_invalidation_queue');
}

add_action('check_cache_invalidation_queue', 'check_cache_invalidation_queue_function');
