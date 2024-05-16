<?php
require_once __DIR__ . '/interfaces/CacheInvalidationTrigger.php';
require_once __DIR__ . '/interfaces/CacheInvalidationTarget.php';
require_once __DIR__ . '/interfaces/CacheInvalidationTarget.php';
require_once __DIR__ . '/triggers/TaxonomyTrigger.php';
require_once __DIR__ . '/triggers/PostTypeTrigger.php';
require_once __DIR__ . '/triggers/DateFieldTrigger.php';
require_once __DIR__ . '/targets/TemplatePageTarget.php';
require_once __DIR__ . '/targets/GutenbergComponentTarget.php';
require_once __DIR__ . '/targets/HomePageTarget.php';
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
     * Triggers cache invalidation if conditions are met.
     */
    public function invalidateCache(): void {
        foreach ($this->triggers as $trigger) {
            if ($trigger->shouldInvalidate()) {
                foreach ($trigger->getTargetsToInvalidate() as $target) {
                    $target->invalidate();
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
        $this->invalidateCacheForPost($postId);
    }


    /**
     * Handles post delete actions to potentially invalidate cache.
     * Note: $postId is passed as a parameter because this function is a callback for WordPress hooks
     * that provide the post ID when a post is deleted. This ID is used to determine which cache entries to invalidate.
     * @param int $postId ID of the post being deleted.
     */
    public function onPostDelete(int $postId): void {
        $this->invalidateCacheForPost($postId);
    }


    /**
     * Handles term change actions to potentially invalidate cache.
     * Note: $termId is passed as a parameter because this function is a callback for WordPress hooks
     * that provide the term ID. While $termId is not used within this method, it is necessary to
     * match the expected signature for WordPress action hooks.
     * @param int $termId ID of the term being modified.
     */
    public function onTermChange(int $termId): void {
        $this->invalidateCacheForTerm();
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
        foreach ($this->triggers as $trigger) {
            if ($trigger instanceof DateFieldTrigger && $trigger->matchesField($meta_key)) {
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
        $validTriggerTypes = ['taxonomy', 'postType', 'dateField'];
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
                $this->triggers[] = new PostTypeTrigger($key, $targetObjects);
                break;
            case 'dateField':
                $this->processDateFieldTriggers($key, $data, $targetObjects);
                break;
        }
    }

    /**
     * Processes date field triggers.
     * @param string $postType Post type related to the trigger.
     * @param array $dateTrigger Specific configuration for date trigger.
     * @param array $targetObjects Targets to be invalidated.
     */
    private function processDateFieldTriggers(string $postType, array $dateTrigger, array $targetObjects): void {
        if ($this->isValidDateTrigger($dateTrigger)) {
            $this->triggers[] = new DateFieldTrigger(
                $postType,
                $dateTrigger['fieldNames'],
                $targetObjects
            );
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
     * Validates date trigger configuration.
     * @param array $dateTrigger Date trigger configuration to validate.
     * @return bool Returns true if the configuration is valid.
     */
    private function isValidDateTrigger(array $dateTrigger): bool {
        if (empty($dateTrigger['fieldNames'])) {
            trigger_error("Error in fieldNames configuration: 'fieldNames' is required.", E_USER_WARNING);
            return false;
        }

        return true;
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
            default:
                trigger_error("Invalid target type specified: '$type'. Allowed types are 'template', 'gutenberg', 'home'.", E_USER_WARNING);
                return null;
        }
    }

    /**
     * Handles invalidation for specific posts, potentially based on post type triggers.
     * @param int $postId ID of the post to check for cache invalidation.
     */
    private function invalidateCacheForPost(int $postId): void {
        foreach ($this->triggers as $trigger) {
            if ($trigger instanceof PostTypeTrigger && $trigger->shouldInvalidate()) {
                foreach ($trigger->getTargetsToInvalidate() as $target) {
                    $target->invalidate();
                }
            } elseif ($trigger instanceof DateFieldTrigger) {
                $trigger->queueForCheck($postId);
            }
        }
    }

    /**
     * Handles invalidation for terms, potentially based on taxonomy triggers.
     * @param int $termId ID of the term to check for cache invalidation.
     */
    private function invalidateCacheForTerm(): void {
        foreach ($this->triggers as $trigger) {
            if ($trigger instanceof TaxonomyTrigger && $trigger->shouldInvalidate()) {
                foreach ($trigger->getTargetsToInvalidate() as $target) {
                    $target->invalidate();
                }
            }
        }
    }

    /**
     * Registers necessary WordPress hooks for triggering cache invalidation.
     */
    private function registerHooks(): void {
        // Register WordPress hooks
        add_action('save_post', [$this, 'onPostSave']);
        add_action('delete_post', [$this, 'onPostDelete']);
        add_action('created_term', [$this, 'onTermChange']);
        add_action('edited_term', [$this, 'onTermChange']);
        add_action('delete_term', [$this, 'onTermChange']);
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
            id INT NOT NULL AUTO_INCREMENT,
            post_type VARCHAR(255) NOT NULL,
            invalidation_date DATETIME NOT NULL,
            checked_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Include WordPress database upgrade file
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
