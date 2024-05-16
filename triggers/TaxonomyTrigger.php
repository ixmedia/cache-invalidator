<?php
require_once __DIR__ . '/../interfaces/CacheInvalidationTrigger.php';

class TaxonomyTrigger implements CacheInvalidationTrigger {
    private string $taxonomyName;
    private array $targets;

    public function __construct(string $taxonomyName, array $targets) {
        $this->taxonomyName = $taxonomyName;
        $this->targets = $targets;
    }

    /**
     * @return CacheInvalidationTarget[]
     */
    public function getTargetsToInvalidate(): array {
        return $this->targets;
    }

    public function shouldInvalidate(): bool {
        // Check if the current taxonomy matches the specified taxonomy name
        $currentTaxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
        return $currentTaxonomy === $this->taxonomyName;
    }

}
