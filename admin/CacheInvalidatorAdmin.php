<?php
class CacheInvalidatorAdmin {
    private $optionName = 'cache_invalidator_options';
    private $optionFilePath;

    public function __construct() {
        $themeDir = get_template_directory();
        $this->optionFilePath = $themeDir . '/cache-invalidator/config.json';

        // Assurez-vous que le dossier existe
        if (!file_exists($themeDir . '/cache-invalidator')) {
            mkdir($themeDir . '/cache-invalidator');
        }

        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'settingsInit']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        add_action('plugins_loaded', [$this, 'loadTextDomain']);
    }

    /**
     * Load the text domain for translation
     */
    public function loadTextDomain() {
        load_plugin_textdomain('cache_invalidator', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Add settings menu in the admin panel
     */
    public function addAdminMenu() {
        add_options_page(
            __('Cache Invalidation Settings', 'cache_invalidator'),
            __('Cache Invalidation', 'cache_invalidator'),
            'manage_options',
            'cache_invalidator',
            [$this, 'optionsPage']
        );
    }

    /**
     * Display the settings page
     */
    public function optionsPage() {
        ?>
        <div class="wrap cache-inv-wrap">
            <h1><?php _e('Cache Invalidation Settings', 'cache_invalidator'); ?></h1>
            <form action="options.php" method="post">
                <?php settings_fields($this->optionName); ?>

                <div class="cache-inv-card">
                    <div class="cache-inv-card-header">
                        <h2>
                            <span class="dashicons dashicons-admin-post"></span>
                            <?php _e('Post Type Triggers', 'cache_invalidator'); ?>
                        </h2>
                    </div>
                    <div class="cache-inv-card-body">
                        <p class="cache-inv-section-desc"><?php _e('Configure triggers for specific post types.', 'cache_invalidator'); ?></p>
                        <?php $this->postTypeTriggersRender(); ?>
                        <button type="button" id="addPostTypeTrigger" class="button button-primary"><?php _e('+ Add Trigger', 'cache_invalidator'); ?></button>
                    </div>
                </div>

                <div class="cache-inv-card">
                    <div class="cache-inv-card-header">
                        <h2>
                            <span class="dashicons dashicons-tag"></span>
                            <?php _e('Taxonomy Triggers', 'cache_invalidator'); ?>
                        </h2>
                    </div>
                    <div class="cache-inv-card-body">
                        <p class="cache-inv-section-desc"><?php _e('Configure triggers for specific taxonomies.', 'cache_invalidator'); ?></p>
                        <?php $this->taxonomyTriggersRender(); ?>
                        <button type="button" id="addTaxonomyTrigger" class="button button-primary"><?php _e('+ Add Trigger', 'cache_invalidator'); ?></button>
                    </div>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register settings and add settings sections and fields
     */
    public function settingsInit() {
        register_setting($this->optionName, $this->optionName, ['sanitize_callback' => [$this, 'saveOptionsToFile']]);

        // Section for postType triggers
        add_settings_section(
            'cache_invalidator_post_type_section',
            __('Post Type Triggers', 'cache_invalidator'),
            [$this, 'postTypeSectionCallback'],
            'cache_invalidator'
        );

        add_settings_field(
            'cache_invalidator_post_type_triggers',
            '',
            [$this, 'postTypeTriggersRender'],
            'cache_invalidator',
            'cache_invalidator_post_type_section'
        );

        // Section for taxonomy triggers
        add_settings_section(
            'cache_invalidator_taxonomy_section',
            __('Taxonomy Triggers', 'cache_invalidator'),
            [$this, 'taxonomySectionCallback'],
            'cache_invalidator'
        );

        add_settings_field(
            'cache_invalidator_taxonomy_triggers',
            '',
            [$this, 'taxonomyTriggersRender'],
            'cache_invalidator',
            'cache_invalidator_taxonomy_section'
        );
    }

    /**
     * Save options to file
     */
    public function saveOptionsToFile($options) {
        file_put_contents($this->optionFilePath, json_encode($options));
        return $options;
    }

    /**
     * Get options from file
     */
    public function getOptions() {
        if (file_exists($this->optionFilePath)) {
            $json = file_get_contents($this->optionFilePath);
            return json_decode($json, true);
        }
        return [];
    }

    /**
     * Callback for the post type section
     */
    public function postTypeSectionCallback() {
        echo __('Configure triggers for specific post types.', 'cache_invalidator');
    }

    /**
     * Render the post type triggers field
     */
    public function postTypeTriggersRender() {
        $post_types = get_post_types(['public' => true], 'objects');
        $options = $this->getOptions();
        $postTypeTriggers = isset($options['postType']) ? $options['postType'] : [];
        ?>
        <div id="postTypeTriggersRepeater" data-template="<?php echo htmlspecialchars($this->getPostTypeTriggerHtml('__index__', null, $post_types)); ?>">
            <?php foreach ($postTypeTriggers as $index => $settings): ?>
                <?php echo $this->getPostTypeTriggerHtml($index, $settings, $post_types); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Callback for the taxonomy section
     */
    public function taxonomySectionCallback() {
        echo __('Configure triggers for specific taxonomies.', 'cache_invalidator');
    }

    /**
     * Render the taxonomy triggers field
     */
    public function taxonomyTriggersRender() {
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        $options = $this->getOptions();
        $taxonomyTriggers = isset($options['taxonomy']) ? $options['taxonomy'] : [];
        ?>
        <div id="taxonomyTriggersRepeater" data-template="<?php echo htmlspecialchars($this->getTaxonomyTriggerHtml('__index__', null, $taxonomies)); ?>">
            <?php foreach ($taxonomyTriggers as $index => $settings): ?>
                <?php echo $this->getTaxonomyTriggerHtml($index, $settings, $taxonomies); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Generate HTML for post type triggers
     *
     * @param string $index The index of the trigger
     * @param array|null $settings The settings for the trigger
     * @param array $post_types The registered post types
     * @return string The generated HTML
     */
    private function getPostTypeTriggerHtml($index, $settings, $post_types) {
        $typeValue = $settings['type'] ?? '';
        $targets = $settings['targets'] ?? [];
        $timeFields = $settings['timeFields'] ?? [];

        $post_types_array = [];

        // Reorder posts by their label
        foreach ($post_types as $type) {
            $post_types_array[$type->name] = $type->label;
        }
        collator_asort(collator_create('root'), $post_types_array);

        ob_start();
        ?>
        <div class="repeater-item">
            <div class="repeater-item-header">
                <span class="dashicons dashicons-admin-post"></span>
                <select name="<?php echo $this->optionName; ?>[postType][<?php echo esc_attr($index); ?>][type]" onchange="toggleTargetFields(this)">
                    <option value=""><?php _e('Select post type', 'cache_invalidator'); ?></option>
                    <?php foreach ($post_types_array as $typeName => $typeLabel): ?>
                        <option value="<?php echo esc_attr($typeName); ?>" <?php selected($typeValue, $typeName); ?>>
                            <?php echo esc_html($typeLabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="repeater-delete delete" aria-label="<?php _e('Remove trigger', 'cache_invalidator'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
            <div class="repeater-item-body">

                <div class="target-repeater" style="<?php echo empty($typeValue) ? 'display:none;' : ''; ?>">
                    <div class="cache-inv-subsection-title">
                        <span class="dashicons dashicons-location"></span>
                        <?php _e('Targets', 'cache_invalidator'); ?>
                    </div>
                    <div class="target-items" data-template="<?php echo htmlspecialchars($this->getTargetHtml($index, 'postType', '__target_index__')); ?>">
                        <?php foreach ($targets as $targetIndex => $target): ?>
                            <?php echo $this->getTargetHtml($index, 'postType', $targetIndex, $target); ?>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button button-small add-target"><?php _e('+ Add Target', 'cache_invalidator'); ?></button>
                </div>

                <div class="time-fields-repeater" style="<?php echo empty($typeValue) ? 'display:none;' : ''; ?>">
                    <div class="cache-inv-subsection-title">
                        <span class="dashicons dashicons-clock"></span>
                        <?php _e('Time Fields', 'cache_invalidator'); ?>
                    </div>
                    <div class="time-fields-items" data-template="<?php echo htmlspecialchars($this->getTimeFieldHtml($index, '__time_field_index__')); ?>">
                        <?php foreach ($timeFields as $timeFieldIndex => $timeField): ?>
                            <?php echo $this->getTimeFieldHtml($index, $timeFieldIndex, $timeField); ?>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button button-small add-time-field"><?php _e('+ Add Time Field', 'cache_invalidator'); ?></button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate HTML for taxonomy triggers
     *
     * @param string $index The index of the trigger
     * @param array|null $settings The settings for the trigger
     * @param array $taxonomies The registered taxonomies
     * @return string The generated HTML
     */
    private function getTaxonomyTriggerHtml($index, $settings, $taxonomies) {
        $typeValue = $settings['type'] ?? '';
        $targets = $settings['targets'] ?? [];
        ob_start();
        ?>
        <div class="repeater-item">
            <div class="repeater-item-header">
                <span class="dashicons dashicons-tag"></span>
                <select name="<?php echo $this->optionName; ?>[taxonomy][<?php echo esc_attr($index); ?>][type]" onchange="toggleTargetFields(this)">
                    <option value=""><?php _e('Select taxonomy', 'cache_invalidator'); ?></option>
                    <?php foreach ($taxonomies as $taxonomy): ?>
                        <option value="<?php echo esc_attr($taxonomy->name); ?>" <?php selected($typeValue, $taxonomy->name); ?>>
                            <?php echo esc_html($taxonomy->label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="repeater-delete delete" aria-label="<?php _e('Remove trigger', 'cache_invalidator'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
            <div class="repeater-item-body">

                <div class="target-repeater" style="<?php echo empty($typeValue) ? 'display:none;' : ''; ?>">
                    <div class="cache-inv-subsection-title">
                        <span class="dashicons dashicons-location"></span>
                        <?php _e('Targets', 'cache_invalidator'); ?>
                    </div>
                    <div class="target-items" data-template="<?php echo htmlspecialchars($this->getTargetHtml($index, 'taxonomy', '__target_index__')); ?>">
                        <?php foreach ($targets as $targetIndex => $target): ?>
                            <?php echo $this->getTargetHtml($index, 'taxonomy', $targetIndex, $target); ?>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button button-small add-target"><?php _e('+ Add Target', 'cache_invalidator'); ?></button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate HTML for time fields
     *
     * @param string $postTypeIndex The index of the post type trigger
     * @param string $timeFieldIndex The index of the time field
     * @param string|null $timeField The time field value
     * @return string The generated HTML
     */
    private function getTimeFieldHtml($postTypeIndex, $timeFieldIndex = '__time_field_index__', $timeField = '') {
        ob_start();
        ?>
        <div class="time-field-item">
            <input type="text" name="<?php echo $this->optionName; ?>[postType][<?php echo esc_attr($postTypeIndex); ?>][timeFields][<?php echo esc_attr($timeFieldIndex); ?>]" placeholder="<?php _e('Time Field', 'cache_invalidator'); ?>" value="<?php echo esc_attr($timeField); ?>">
            <button type="button" class="remove-icon" onclick="removeTimeField(this)" aria-label="<?php _e('Remove Time Field', 'cache_invalidator'); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate HTML for targets
     *
     * @param string $elementIndex The index of the post type or taxonomy trigger
     * @param string $targetIndex The index of the target
     * @param array|null $target The target settings
     * @return string The generated HTML
     */
    private function getTargetHtml($elementIndex, $elementType, $targetIndex = '__target_index__', $target = null) {
        $targetType = $target['type'] ?? '';
        $targetValue = $target['value'] ?? '';
        ob_start();
        ?>
        <div class="target-item">
            <select name="<?php echo $this->optionName; ?>[<?php echo $elementType; ?>][<?php echo esc_attr($elementIndex); ?>][targets][<?php echo esc_attr($targetIndex); ?>][type]" onchange="toggleTargetValueInput(this)">
                <option value=""><?php _e('Select type', 'cache_invalidator'); ?></option>
                <option value="template" <?php selected($targetType, 'template'); ?>><?php _e('Template', 'cache_invalidator'); ?></option>
                <option value="gutenberg" <?php selected($targetType, 'gutenberg'); ?>><?php _e('Gutenberg', 'cache_invalidator'); ?></option>
                <option value="home" <?php selected($targetType, 'home'); ?>><?php _e('Home', 'cache_invalidator'); ?></option>
                <option value="layout" <?php selected($targetType, 'layout'); ?>><?php _e('Layout', 'cache_invalidator'); ?></option>
                <option value="archive" <?php selected($targetType, 'archive'); ?>><?php _e('Archive', 'cache_invalidator'); ?></option>
            </select>
            <input type="text" name="<?php echo $this->optionName; ?>[<?php echo $elementType; ?>][<?php echo esc_attr($elementIndex); ?>][targets][<?php echo esc_attr($targetIndex); ?>][value]" placeholder="<?php _e('Target Value', 'cache_invalidator'); ?>" value="<?php echo esc_attr($targetValue); ?>" <?php if (!in_array($targetType, ['template', 'gutenberg', 'archive'])) echo 'style="display:none;"'; ?>>
            <button type="button" class="remove-icon" onclick="removeTarget(this)" aria-label="<?php _e('Remove Target', 'cache_invalidator'); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueueAdminScripts() {
        wp_enqueue_script('cache-invalidator-admin-script', plugin_dir_url(__FILE__) . 'assets/admin.js', [], null, true);
        wp_enqueue_style('cache-invalidator-admin-style', plugin_dir_url(__FILE__) . 'assets/admin.css', [], null);
    }
}
