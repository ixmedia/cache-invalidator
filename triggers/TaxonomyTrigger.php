<?php
require_once __DIR__ . '/../interfaces/CacheInvalidationTrigger.php';

class TaxonomyTrigger implements CacheInvalidationTrigger {
    private string $taxonomyName;
    private array $targets;

    public function __construct(string $taxonomyName, array $targets) {
        $this->taxonomyName = $taxonomyName;
        $this->targets = $targets;
    }

    public function getTriggerId(): string
    {
        return $this->taxonomyName;
    }

    /**
     * @return CacheInvalidationTarget[]
     */
    public function getTargetsToInvalidate(): array {
        return $this->targets;
    }

    public function shouldInvalidate($taxonomy): bool {
        // Check if the current taxonomy matches the specified taxonomy name
        return $taxonomy === $this->getTriggerId();
    }

}
