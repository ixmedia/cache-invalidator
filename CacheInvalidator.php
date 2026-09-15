<?php
require_once __DIR__ . '/interfaces/CacheInvalidationTrigger.php';
require_once __DIR__ . '/interfaces/CacheManager.php';
require_once __DIR__ . '/interfaces/CacheInvalidationConfigParser.php';
require_once __DIR__ . '/triggers/TaxonomyTrigger.php';
require_once __DIR__ . '/triggers/PostTypeTrigger.php';
require_once __DIR__ . '/targets/TemplatePageTarget.php';
require_once __DIR__ . '/targets/GutenbergComponentTarget.php';
require_once __DIR__ . '/targets/HomePageTarget.php';
require_once __DIR__ . '/targets/LayoutTarget.php';
require_once __DIR__ . '/targets/ArchivePageTarget.php';
require_once __DIR__ . '/managers/W3TCManager.php';
require_once __DIR__ . '/managers/WPManager.php';

class CacheInvalidator {
    /**
     * Holds an array of triggers for cache invalidation.
     * @var CacheInvalidationTrigger[]
     */
    private array $triggers = [];
    private CacheInvalidationConfigParser $configParser;

    /**
     * Constructor initializes triggers based on provided configuration and registers WordPress hooks.
     * @param array $config Configuration for initializing triggers.
     */
    public function __construct(CacheInvalidationConfigParser $configParser) {
        $this->configParser = $configParser;
        $this->triggers = $this->configParser->parseConfig();
        $this->createCacheInvalidationTable();
        $this->registerHooks();
    }

    /**
     * Adds a new trigger to the manager.
     * @param CacheInvalidationTrigger $trigger The trigger to add.
     */
    public function addTrigger(CacheInvalidationTrigger $trigger): void {
        $this->triggers[] = $trigger;
    }

    /**
     * Get all triggers.
     * @return CacheInvalidationTrigger[] Array of triggers.
     */
    public function getTriggers(): array {
        return $this->triggers;
    }

    /**
     * Process cache invalidation based on database entries.
     */
    public function processQueue(): void {
        $rows = CacheInvalidationHelper::getCacheInvalidationQueueEntries();

        foreach ($rows as $row) {
            $postType = $row['post_type'];
            $postId = $row['post_id'];
            $invalidationDate = $row['invalidation_date'];

            if (strpos($postType, 'gutenberg:') === 0) {
                $blockName = substr($postType, strlen('gutenberg:'));
                if ($blockName !== '') {
                    $cacheManager = function_exists('w3tc_flush_post') ? new W3TCManager() : new WPManager();
                    $target = new GutenbergComponentTarget($blockName, $cacheManager);
                    $target->invalidate();
                }
                CacheInvalidationHelper::deleteQueueEntry($postType, (int) $postId, $invalidationDate);
                continue;
            }

            $postStatus = get_post_status($postId);

            foreach ($this->getTriggers() as $trigger) {
                if ($trigger instanceof PostTypeTrigger && $trigger->getTriggerId() === $postType && $postStatus == "publish") {
                    foreach ($trigger->getTargetsToInvalidate() as $target) {
                        $target->invalidate();
                    }
                    CacheInvalidationHelper::deleteQueueEntry($postType, $postId, $invalidationDate);
                }
            }
        }
    }

    /**
     * Handles post save actions to potentially invalidate cache.
     * Note: $postId is passed as a parameter because this function is a callback for WordPress hooks
     * that provide the post ID when a post is saved. This ID is used to determine which cache entries to invalidate.
     * @param int $postId ID of the post being saved.
     */
    public function onPostSave(int $postId): void {
        // Vérifiez si c'est une sauvegarde automatique pour éviter des boucles infinies
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        foreach ($this->triggers as $trigger) {
            if ($trigger instanceof PostTypeTrigger) {

                if ($trigger->shouldInvalidate($postId)) {
                    foreach ($trigger->getTargetsToInvalidate() as $target) {
                        $target->invalidate();
                    }
                }
                $trigger->queueForCheck($postId);
            }
        }
    }

    /**
     * Handles term change actions to potentially invalidate cache.
     * Note: $termId is passed as a parameter because this function is a callback for WordPress hooks
     * that provide the term ID. While $termId is not used within this method, it is necessary to
     * match the expected signature for WordPress action hooks.
     * @param int $termId ID of the term being modified.
     * @param int $ttId Term taxonomy ID being modified.
     * @param string $taxonomy Taxonomy slug.
     */
    public function onTermChange(int $termId, int $ttId, string $taxonomy): void {
        // Vérifiez si c'est une sauvegarde automatique pour éviter des boucles infinies
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        foreach ($this->triggers as $trigger) {
            if ($trigger instanceof TaxonomyTrigger && $trigger->shouldInvalidate($taxonomy)) {
                foreach ($trigger->getTargetsToInvalidate() as $target) {
                    $target->invalidate();
                }
            }
        }
    }

    /**
     * Handles post meta change actions to potentially queue checks for cache invalidation.
     * Note: This function is a callback for WordPress hooks that provide metadata details. $post_id is
     * particularly crucial as it helps identify which post's cache may need to be invalidated based on metadata changes.
     * @param mixed $meta_id Meta ID of the updated metadata entry.
     * @param int $post_id Post ID associated with the metadata.
     * @param string $meta_key Meta key that was changed.
     * @param mixed $meta_value New meta value.
     */
    public function onPostMetaChange($meta_id, $post_id, $meta_key, $meta_value): void {
        // Vérifiez si c'est une sauvegarde automatique pour éviter des boucles infinies
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Vérifiez si c'est le meta key _edit_lock
        if ($meta_key == '_edit_lock') {
            return;
        }

        foreach ($this->triggers as $trigger) {
            if ($trigger instanceof PostTypeTrigger && $trigger->matchesField($meta_key)) {
                $trigger->queueForCheck($post_id);
            }
        }
    }

    public function onOptionAdd(string $option, $value): void {
        $this->onOptionSave($option);
    }

    public function onOptionUpdate(string $option, $old_value, $value): void {
        $this->onOptionSave($option);
    }

    public function onOptionDelete(string $option): void {
        $this->onOptionSave($option);
    }

    /**
     * Registers necessary WordPress hooks for triggering cache invalidation.
     */
    private function registerHooks(): void {
        // Register WordPress hooks
        add_action('save_post', [$this, 'onPostSave']);
        add_action('delete_post', [$this, 'onPostSave']);
        add_action('added_option', [$this, 'onOptionAdd'], 10, 2);
        add_action('updated_option', [$this, 'onOptionUpdate'], 10, 3);
        add_action('deleted_option', [$this, 'onOptionDelete']);
        add_action('created_term', [$this, 'onTermChange'], 10, 3);
        add_action('edited_term', [$this, 'onTermChange'], 10, 3);
        add_action('delete_term', [$this, 'onTermChange'], 10, 3);
        add_action('updated_post_meta', [$this, 'onPostMetaChange'], 10, 4);
        add_action('added_post_meta', [$this, 'onPostMetaChange'], 10, 4);
    }

    /**
     * Creates the cache invalidation queue table.
     *
     * This function includes the WordPress database upgrade file (`upgrade.php`) because it contains the `dbDelta()` function,
     * which is necessary for creating and updating database tables using SQL queries with proper WordPress conventions.
     */
    private function createCacheInvalidationTable(): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cache_invalidation_queue';

        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            post_type varchar(255) NOT NULL,
            post_id bigint NOT NULL,
            invalidation_date datetime NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (post_type, post_id, invalidation_date),
            INDEX idx_invalidation_date (invalidation_date)
        ) $charset_collate;";

        // Include WordPress database upgrade file
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    private function onOptionSave(string $option): void {
        // Vérifiez si c'est une sauvegarde automatique pour éviter des boucles infinies
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Skip options with unsupported names
        if (strpos($option, 'options_') !== 0) {
            return;
        }

        foreach ($this->triggers as $trigger) {
            if ($trigger instanceof PostTypeTrigger) {
                if ($trigger->getTriggerId() == 'acf-ui-options-page') {
                    foreach ($trigger->getTargetsToInvalidate() as $target) {
                        $target->invalidate();
                    }
                }
            }
        }
    }
}
