<?php

class CacheInvalidationHelper {
    public static function insertIntoQueue(int $postId, string $typeName, string $triggerType, array $invalidationDates): void {
        global $wpdb;

        $table = $wpdb->prefix . 'cache_invalidation_queue';

        // Delete existing entries with the same post_type, and post_id
        $wpdb->delete(
            $table,
            [
                'post_type' => $typeName,
                'post_id' => $postId
            ],
            [
                '%s', // format for typeName
                '%d'  // format for postId
            ]
        );

        // Loop through each invalidation date
        foreach ($invalidationDates as $invalidationDate) {
            // Insert the new entry
            $wpdb->insert(
                $table,
                [
                    'post_type' => $typeName,
                    'invalidation_date' => $invalidationDate,
                    'created_at' => current_time('mysql'),
                    'post_id' => $postId
                ],
                [
                    '%s', // format for typeName
                    '%s', // format for invalidationDate
                    '%s', // format for createdAt
                    '%d'  // format for postId
                ]
            );
        }
    }


    /**
     * Retrieves entries from the cache invalidation queue where invalidation_date is in the past.
     * @return array Array of rows from the cache invalidation queue.
     */
    public static function getCacheInvalidationQueueEntries(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'cache_invalidation_queue';
        $current_time = current_time('mysql');

        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE invalidation_date <= %s", $current_time), ARRAY_A);
    }

    /**
     * Deletes an entry from the cache invalidation queue.
     * @param string $postType The post type of the entry.
     * @param int $postId The post ID of the entry.
     * @param string $triggerType The trigger type of the entry.
     * @param string $invalidationDate The invalidation date of the entry.
     */
    public static function deleteQueueEntry(string $postType, int $postId, string $invalidationDate): void {
        global $wpdb;
        $table = $wpdb->prefix . 'cache_invalidation_queue';
        $wpdb->delete(
            $table,
            [
                'post_type' => $postType,
                'post_id' => $postId,
                'invalidation_date' => $invalidationDate
            ],
            [
                '%s', // format for postType
                '%d', // format for postId
                '%s'  // format for invalidationDate
            ]
        );
    }
}
