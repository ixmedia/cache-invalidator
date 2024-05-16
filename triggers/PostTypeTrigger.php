<?php
require_once __DIR__ . '/../interfaces/CacheInvalidationTrigger.php';

class PostTypeTrigger implements CacheInvalidationTrigger {
    private string $postTypeName;
    private array $targets;

    public function __construct(string $postTypeName, array $targets) {
        $this->postTypeName = $postTypeName;
        $this->targets = $targets;
    }

    /**
     * @return CacheInvalidationTarget[]
     */
    public function getTargetsToInvalidate(): array {
        return $this->targets;
    }

    public function shouldInvalidate(): bool {
        // Check if the current post type matches the specified post type name
        $post = get_post(get_the_ID());
        return $post && $post->post_type === $this->postTypeName && $post->post_status == "publish";
    }

}
