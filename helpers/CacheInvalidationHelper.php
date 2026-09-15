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
            $startDate = new DateTime($invalidationDate);
            $endDate = new DateTime($invalidationDate);
            $endDate = $endDate->setTime(23, 59, 59);

            // Insert the new entry
            $result = $wpdb->insert(
                $table,
                [
                    'post_type' => $typeName,
                    'invalidation_date' => $startDate->format('Y-m-d H:i:s'),
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
            $result = $wpdb->insert(
                $table,
                [
                    'post_type' => $typeName,
                    'invalidation_date' => $endDate->format('Y-m-d H:i:s'),
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
     * Inserts into the queue only if no entry with the same post_type and post_id already exists.
     * Use this for scheduled re-queuing so an existing future entry is never overwritten.
     *
     * @param int    $postId
     * @param string $typeName
     * @param string $triggerType
     * @param array  $invalidationDates
     */
    public static function insertIntoQueueIfNotExists(int $postId, string $typeName, string $triggerType, array $invalidationDates): void {
        global $wpdb;
        $table = $wpdb->prefix . 'cache_invalidation_queue';

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE post_type = %s AND post_id = %d",
            $typeName,
            $postId
        ));

        if ((int) $exists === 0) {
            self::insertIntoQueue($postId, $typeName, $triggerType, $invalidationDates);
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
