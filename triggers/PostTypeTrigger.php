<?php
require_once __DIR__ . '/../interfaces/CacheInvalidationTrigger.php';
require_once __DIR__ . '/../helpers/CacheInvalidationHelper.php';

class PostTypeTrigger implements CacheInvalidationTrigger {
    private array $timeFields;
    private string $postTypeName;
    private array $targets;

    public function __construct(string $postTypeName, array $timeFields, array $targets) {
        $this->postTypeName = $postTypeName;
        $this->targets = $targets;
        $this->timeFields = $timeFields;
    }

    public function getTriggerId(): string
    {
        return $this->postTypeName;
    }

    /**
     * @return CacheInvalidationTarget[]
     */
    public function getTargetsToInvalidate(): array {
        return $this->targets;
    }

    public function shouldInvalidate($postId): bool {
        // Check if the current post type matches the specified post type name
        $post = get_post($postId);

        return $post && $post->post_type === $this->getTriggerId();
    }

    public function queueForCheck(int $postId): void {
        $postStatus = get_post_status($postId); // Get the post status

        $invalidationDates = [];

        if ($postStatus === "publish" || $postStatus === "future") {

            foreach ($this->timeFields as $timeField) {
                $fieldValue = get_post_meta($postId, $timeField, true);
                if ($fieldValue) {
                    $invalidationDates[] = $fieldValue;
                }
            }
        }

        if ($postStatus == "future") {
            $invalidationDates[] = get_post_field('post_date', $postId);
        }

        CacheInvalidationHelper::insertIntoQueue($postId, $this->getTriggerId(), get_class($this), $invalidationDates);
    }

    public function matchesField(string $metaKey): bool {
        return in_array($metaKey, $this->timeFields, true);
    }
}
