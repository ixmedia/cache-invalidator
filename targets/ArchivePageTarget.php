<?php
require_once __DIR__ . '/../interfaces/CacheInvalidationTarget.php';
require_once __DIR__ . '/../interfaces/CacheManager.php';

class ArchivePageTarget implements CacheInvalidationTarget {
    private string $postType;
    private CacheManager $cacheManager;

    public function __construct(string $postType, CacheManager $cacheManager) {
        $this->postType = $postType;
        $this->cacheManager = $cacheManager;
    }

    public function getTargetId(): string {
        return $this->postType;
    }

    public function invalidate(): void {
        $urls = $this->getArchiveUrls();

        if (!empty($urls)) {
            foreach ($urls as $url) {
                $this->cacheManager->invalidateUrl($url);
            }
        } else {
            trigger_error("Cache Invalidator: Failed to resolve archive URL for post type '{$this->postType}'.", E_USER_WARNING);
        }
    }

    /**
     * Retrieve the post type archive URL(s), one per active language when WPML is in use.
     *
     * @return array An array of archive URLs.
     */
    private function getArchiveUrls(): array {
        $urls = [];

        // Check if WPML is active
        if (defined('ICL_SITEPRESS_VERSION')) {
            $languages = apply_filters('wpml_active_languages', null, 'orderby=id&order=desc');

            if (!empty($languages)) {
                $current_language = apply_filters('wpml_current_language', null);

                foreach ($languages as $language) {
                    // Switch WPML to this language so the archive link is generated for it
                    do_action('wpml_switch_language', $language['language_code']);

                    $url = get_post_type_archive_link($this->postType);
                    if ($url) {
                        $urls[] = $url;
                    }
                }

                // Restore the original language
                do_action('wpml_switch_language', $current_language);
            }
        }

        // If no URLs were found (WPML inactive or no languages), use the default archive link
        if (empty($urls)) {
            $url = get_post_type_archive_link($this->postType);
            if ($url) {
                $urls[] = $url;
            }
        }

        return array_unique($urls);
    }
}
