<?php
require_once __DIR__ . '/../interfaces/CacheInvalidationTrigger.php';

class DateFieldTrigger implements CacheInvalidationTrigger {
    private string $postTypeName;
    private array $fieldNames;
    private array $targets;

    public function __construct(string $postTypeName, array $fieldNames, array $targets) {
        $this->postTypeName = $postTypeName;
        $this->fieldNames = $fieldNames;
        $this->targets = $targets;
    }

    /**
     * @return CacheInvalidationTarget[]
     */
    public function getTargetsToInvalidate(): array {
        return $this->targets;
    }

    public function shouldInvalidate(): bool {
        // This will be checked by the cron job, so it returns false here
        return false;
    }


    public function queueForCheck(int $postId): void {
        global $wpdb;
        foreach ($this->fieldNames as $fieldName) {
            $fieldValue = get_post_meta($postId, $fieldName, true);
            $table = $wpdb->prefix . 'cache_invalidation_queue';
            $wpdb->insert(
                $table,
                [
                    'post_type' => $this->postTypeName,
                    'invalidation_date' => $fieldValue,
                    'created_at' => new DateTime(),
                ]
            );

        }

    }

    public function matchesField(string $metaKey): bool {
        return in_array($metaKey, $this->fieldNames, true);
    }
}
