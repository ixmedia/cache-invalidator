<?php
require_once __DIR__ . '/../interfaces/CacheInvalidationTarget.php';
require_once __DIR__ . '/../interfaces/CacheManager.php';

class HomePageTarget implements CacheInvalidationTarget {
    private CacheManager $cacheManager;

    public function __construct(CacheManager $cacheManager) {
        $this->cacheManager = $cacheManager;
    }


    public function getTargetId(): string {
        return "home";
    }

    public function invalidate(): void {
        // Get the home page IDs to use
        $home_ids = $this->getHomePageIds();

        if (!empty($home_ids)) {
            foreach ($home_ids as $home_id) {
                $this->cacheManager->invalidateCache($home_id);
            }
        } else {
            trigger_error("Cache Validator: Failed to get home page ID." , E_ERROR);
        }
    }

    /**
     * Retrieve an array of home page IDs for each language.
     *
     * This function retrieves the home page ID for each active language in WPML,
     * or the default home page ID if WPML is not active.
     *
     * @return array An array of home page IDs.
     */
    private function getHomePageIds(): array {
        // Initialize an array to store home page IDs
        $home_ids = [];

        // Check if WPML is active
        if (defined('ICL_SITEPRESS_VERSION')) {
            // Get all active languages from WPML
            $languages = apply_filters('wpml_active_languages', null, 'orderby=id&order=desc');

            foreach ($languages as $language) {
                // Get the home page ID in this language
                $home_id = apply_filters('wpml_object_id', get_option('page_on_front'), 'page', true, $language['language_code']);

                if ($home_id) {
                    // Store the home page ID in the array
                    $home_ids[] = $home_id;
                }
            }
        }

        // If no home page IDs are found, use the default ID
        if (empty($home_ids)) {
            $home_ids[] = get_option('page_on_front');
        }

        return $home_ids;
    }
}
