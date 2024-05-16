<?php
require_once __DIR__ . '/../interfaces/CacheInvalidationTarget.php';

class TemplatePageTarget implements CacheInvalidationTarget {
    private CacheManager $cacheManager;
    private string $templateName;

    public function __construct(string $templateName, CacheManager $cacheManager) {
        $this->templateName = $templateName;
        $this->cacheManager = $cacheManager;
    }

    public function getTargetId(): string {
        return $this->templateName;
    }

    public function invalidate(): void {
        // Get the page IDs with the specified template
        $page_ids = $this->getPageIdsWithTemplate();

        if (!empty($page_ids)) {
            foreach ($page_ids as $page_id) {
                $this->cacheManager->invalidateCache($page_id);
            }
        }
    }

    /**
     * Retrieve an array of page IDs using the specified template.
     *
     * This function constructs and executes an SQL query to retrieve
     * the IDs of pages using the specified template.
     *
     * @return array An array of page IDs.
     */
    private function getPageIdsWithTemplate() {
        global $wpdb;

        // Construct SQL query to get page IDs with the specified template
        $sql = $wpdb->prepare("
            SELECT p.ID
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'page'
            AND p.post_status = 'publish'
            AND pm.meta_key = '_wp_page_template'
            AND pm.meta_value = %s
        ", $this->getTargetId());

        // Execute the query
        return $wpdb->get_col($sql);
    }
}
