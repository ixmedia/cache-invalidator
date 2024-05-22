<?php

require_once __DIR__ . '/../interfaces/CacheInvalidationTrigger.php';
require_once __DIR__ . '/../interfaces/CacheInvalidationConfigParser.php';
require_once __DIR__ . '/../triggers/TaxonomyTrigger.php';
require_once __DIR__ . '/../triggers/PostTypeTrigger.php';
require_once __DIR__ . '/../targets/TemplatePageTarget.php';
require_once __DIR__ . '/../targets/GutenbergComponentTarget.php';
require_once __DIR__ . '/../targets/HomePageTarget.php';
require_once __DIR__ . '/../targets/LayoutTarget.php';
require_once __DIR__ . '/../managers/W3TCManager.php';
require_once __DIR__ . '/../managers/WPManager.php';

class PhpFileConfigParser implements CacheInvalidationConfigParser {
    private CacheManager $cacheManager;
    private array $config;

    public function __construct(array $config) {
        $this->config = $config;
        $this->cacheManager = function_exists('w3tc_flush_post') ? new W3TCManager() : new WPManager();
    }

    /**
     * Parses the configuration array and returns an array of CacheInvalidationTrigger objects.
     * @param array $config Configuration array detailing triggers.
     * @return CacheInvalidationTrigger[] Array of parsed triggers.
     */
    public function parseConfig(): array {
        $triggers = [];

        if (empty($this->config)) {
            return $triggers;
        }

        foreach ($this->config as $type => $triggerConfigs) {
            if (!$this->isValidTriggerType($type)) {
                continue;
            }

            foreach ($triggerConfigs as $data) {
                if (empty($data['targets'])) {
                    trigger_error("Cache Invalidator: No targets specified for type: '$type'", E_USER_WARNING);
                    continue;
                }

                $targetObjects = $this->createTargetsFromData($data['targets']);
                if (empty($targetObjects)) {
                    continue;
                }

                $triggers[] = $this->createTrigger($type, $data['type'], $data, $targetObjects);
            }
        }

        return $triggers;
    }

    /**
     * Validates if a trigger type is valid.
     * @param string $type Type of the trigger.
     * @return bool Returns true if the trigger type is valid.
     */
    private function isValidTriggerType(string $type): bool {
        $validTriggerTypes = ['taxonomy', 'postType'];
        if (!in_array($type, $validTriggerTypes)) {
            trigger_error("Cache Invalidator: Invalid trigger type specified: '$type'. Allowed types are " . implode(', ', $validTriggerTypes) . ".", E_USER_WARNING);
            return false;
        }
        return true;
    }

    /**
     * Creates a trigger based on the type and configuration data.
     * @param string $type Type of the trigger.
     * @param string $key Identifier for the trigger configuration.
     * @param array $data Configuration data for the trigger.
     * @param array $targetObjects Array of target objects.
     * @return CacheInvalidationTrigger Created trigger object.
     */
    private function createTrigger(string $type, string $key, array $data, array $targetObjects): CacheInvalidationTrigger {
        switch ($type) {
            case 'taxonomy':
                return new TaxonomyTrigger($key, $targetObjects);
            case 'postType':
                $timeFields = $data['timeFields'] ?? [];
                return new PostTypeTrigger($key, $timeFields, $targetObjects);
            default:
                trigger_error("Cache Invalidator: Invalid trigger type specified: '$type'.", E_USER_WARNING);
                return null;
        }
    }

    /**
     * Creates target objects from configuration data.
     * @param array $data Configuration data containing target details.
     * @return CacheInvalidationTarget[] Array of created target objects.
     */
    private function createTargetsFromData(array $targetsData): array {
        $targetObjects = [];

        foreach ($targetsData as $target) {
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
    private function createTarget($type, $value = null): ?CacheInvalidationTarget {
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
                trigger_error("Cache Invalidator: Invalid target type specified: '$type'. Allowed types are 'template', 'gutenberg', 'home', 'layout'.", E_USER_WARNING);
                return null;
        }
    }
}
