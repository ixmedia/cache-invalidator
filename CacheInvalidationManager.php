<?php
require_once __DIR__ . '/interfaces/CacheInvalidationTrigger.php';
require_once __DIR__ . '/interfaces/CacheInvalidationTarget.php';
require_once __DIR__ . '/interfaces/CacheInvalidationTarget.php';
require_once __DIR__ . '/triggers/TaxonomyTrigger.php';
require_once __DIR__ . '/triggers/PostTypeTrigger.php';
require_once __DIR__ . '/targets/TemplatePageTarget.php';
require_once __DIR__ . '/targets/GutenbergComponentTarget.php';
require_once __DIR__ . '/targets/HomePageTarget.php';
require_once __DIR__ . '/targets/LayoutTarget.php';
require_once __DIR__ . '/managers/W3TCManager.php';
require_once __DIR__ . '/managers/WPManager.php';

class CacheInvalidationManager {
    /**
     * Holds an array of triggers for cache invalidation.
     * @var CacheInvalidationTrigger[]
     */
    private array $triggers = [];
    private CacheManager $cacheManager;

    /**
     * Constructor initializes triggers based on provided configuration and registers WordPress hooks.
     * @param array $config Configuration for initializing triggers.
     */
    public function __construct(array $config) {
        $this->cacheManager = $this->isW3TCPluginActive() ? new W3TCManager() : new WPManager();
        $this->createCacheInvalidationTable();
        $this->initializeTriggers($config);
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

        foreach ($this->triggers as $trigger) {
            if ($trigger instanceof PostTypeTrigger && $trigger->matchesField($meta_key)) {
                $trigger->queueForCheck($post_id);
            }
        }
    }

    /**
     * Checks if the W3TC plugin is active.
     * @return bool Returns true if W3TC plugin is active, false otherwise.
     */
    private function isW3TCPluginActive(): bool {
        // Check if the W3TC plugin is active
        // You need to implement this method based on how plugin activation status is determined in WordPress
        // For example, you can check if a specific function or class provided by W3TC exists
        return function_exists('w3tc_flush_post');
    }

    /**
     * Initializes triggers based on the provided configuration.
     * @param array $config Configuration array detailing triggers.
     */
    private function initializeTriggers(array $config): void {
        foreach ($config['triggers'] as $type => $triggers) {
            if (!$this->isValidTriggerType($type)) {
                continue;
            }

            foreach ($triggers as $key => $data) {
                $targetObjects = $this->createTargetsFromData($data);
                if (empty($targetObjects)) {
                    continue;
                }

                $this->createAndAddTrigger($type, $key, $data, $targetObjects);
            }
        }
    }

    /**
     * Validates if a trigger type is valid.
     * @param string $type Type of the trigger.
     * @return bool Returns true if the trigger type is valid.
     */
    private function isValidTriggerType(string $type): bool {
        $validTriggerTypes = ['taxonomy', 'postType'];
        if (!in_array($type, $validTriggerTypes)) {
            trigger_error("Invalid trigger type specified: '$type'. Allowed types are " . implode(', ', $validTriggerTypes) . ".", E_USER_WARNING);
            return false;
        }
        return true;
    }

    /**
     * Creates and adds a trigger based on the type and configuration data.
     * @param string $type Type of the trigger.
     * @param string $key Identifier for the trigger configuration.
     * @param array $data Configuration data for the trigger.
     * @param array $targetObjects Array of target objects.
     */
    private function createAndAddTrigger(string $type, string $key, array $data, array $targetObjects): void {
        switch ($type) {
            case 'taxonomy':
                $this->triggers[] = new TaxonomyTrigger($key, $targetObjects);
                break;
            case 'postType':
                if (!empty($data['timeFields']))
                    $this->triggers[] = new PostTypeTrigger($key, $data['timeFields'], $targetObjects);
                else {
                    $this->triggers[] = new PostTypeTrigger($key, [], $targetObjects);
                }
                break;
        }
    }

    /**
     * Creates target objects from configuration data.
     * @param array $data Configuration data containing target details.
     * @return array Array of created target objects.
     */
    private function createTargetsFromData(array $data): array {
        $targetObjects = [];
        foreach ($data['targets'] as $target) {
            $createdTarget = $this->createTarget($target['type'], $target['value'] ?? null);
            if ($createdTarget !== null) {
                $targetObjects[] = $createdTarget;
            }
        }
        return $targetObjects;
    }


    /**
     * Creates a cache invalidation target based on type and value.
     * @param string $type Type of the target.
     * @param mixed $value Optional value specific to the target type.
     * @return CacheInvalidationTarget|null Created target object or null if the type is invalid.
     */
    private function createTarget($type, $value = null): CacheInvalidationTarget {
        switch ($type) {
            case 'template':
                return new TemplatePageTarget($value, $this->cacheManager);
            case 'gutenberg':
                return new GutenbergComponentTarget($value, $this->cacheManager);
            case 'home':
                return new HomePageTarget($this->cacheManager);
            case 'layout':
                return new LayoutTarget($this->cacheManager);
            default:
                trigger_error("Invalid target type specified: '$type'. Allowed types are 'template', 'gutenberg', 'home'.", E_USER_WARNING);
                return null;
        }
    }

    /**
     * Registers necessary WordPress hooks for triggering cache invalidation.
     */
    private function registerHooks(): void {
        // Register WordPress hooks
        add_action('save_post', [$this, 'onPostSave']);
        add_action('delete_post', [$this, 'onPostSave']);
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
            post_type VARCHAR(255) NOT NULL,
            post_id BIGINT NOT NULL,
            invalidation_date DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (post_type, post_id, invalidation_date),
            INDEX idx_invalidation_date (invalidation_date)
        ) $charset_collate;";

        // Include WordPress database upgrade file
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
