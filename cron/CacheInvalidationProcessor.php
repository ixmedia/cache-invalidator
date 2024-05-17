<?php
require_once __DIR__ . '/../helpers/CacheInvalidationHelper.php';

class CacheInvalidationProcessor {
    private CacheInvalidationManager $manager;

    public function __construct(CacheInvalidationManager $manager) {
        $this->manager = $manager;
    }

    /**
     * Process cache invalidation based on database entries.
     */
    public function processInvalidation() {
        $rows = CacheInvalidationHelper::getCacheInvalidationQueueEntries();

        foreach ($rows as $row) {
            $allValid = true;
            $postType = $row['post_type'];
            $postId = $row['post_id'];
            $invalidationDate = $row['invalidation_date'];
            $postStatus = get_post_status($postId);

            foreach ($this->manager->getTriggers() as $trigger) {
                if ($trigger instanceof PostTypeTrigger && $trigger->getTriggerId() === $postType && $postStatus == "publish") {
                    foreach ($trigger->getTargetsToInvalidate() as $target) {
                        $target->invalidate();
                    }
                }
                else {
                    $allValid = false;
                }
            }
            if ($allValid) CacheInvalidationHelper::deleteQueueEntry($postType, $postId, $invalidationDate);
        }
    }
}


