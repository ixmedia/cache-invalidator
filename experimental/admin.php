<?php
class CacheInvalidatorAdmin {
    public function __construct() {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'settingsInit']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
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
        <div class="wrap">
            <h1><?php _e('Cache Invalidation Settings', 'cache_invalidator'); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('cache_invalidator_options');
                do_settings_sections('cache_invalidator');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register settings and add settings sections and fields
     */
    public function settingsInit() {
        register_setting('cache_invalidator_options', 'cache_invalidator_options');

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
        // Get all registered post types
        $post_types = get_post_types(['public' => true], 'objects');

        // Get the saved options
        $options = get_option('cache_invalidator_options');
        $postTypeTriggers = isset($options['postType']) ? $options['postType'] : [];
        ?>
        <div id="postTypeTriggersRepeater" data-template="<?php echo htmlspecialchars($this->getPostTypeTriggerHtml('__index__', null, $post_types)); ?>">
            <button type="button" id="addPostTypeTrigger" class="button button-primary"><?php _e('Add Post Type Trigger', 'cache_invalidator'); ?></button>
            <?php foreach ($postTypeTriggers as $index => $settings): ?>
                <?php echo $this->getPostTypeTriggerHtml($index, $settings, $post_types); ?>
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
        ob_start();
        ?>
        <div class="repeater-item">
            <select name="cache_invalidator_options[postType][<?php echo esc_attr($index); ?>][type]" onchange="toggleTargetFields(this)">
                <option value=""><?php _e('Select post type', 'cache_invalidator'); ?></option>
                <?php foreach ($post_types as $type): ?>
                    <option value="<?php echo esc_attr($type->name); ?>" <?php selected($typeValue, $type->name); ?>>
                        <?php echo esc_html($type->label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="target-repeater" style="<?php echo empty($typeValue) ? 'display:none;' : ''; ?>">
                <button type="button" class="button add-target"><?php _e('Add Target', 'cache_invalidator'); ?></button>
                <div class="target-items" data-template="<?php echo htmlspecialchars($this->getTargetHtml($index, '__target_index__')); ?>">
                    <?php foreach ($targets as $targetIndex => $target): ?>
                        <?php echo $this->getTargetHtml($index, $targetIndex, $target); ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="button" class="button-link delete" onclick="removePostTypeTrigger(this)"><?php _e('Remove Post Type Trigger', 'cache_invalidator'); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate HTML for targets
     *
     * @param string $postTypeIndex The index of the post type trigger
     * @param string $targetIndex The index of the target
     * @param array|null $target The target settings
     * @return string The generated HTML
     */
    private function getTargetHtml($postTypeIndex, $targetIndex = '__target_index__', $target = null) {
        $targetType = $target['type'] ?? '';
        $targetValue = $target['value'] ?? '';
        ob_start();
        ?>
        <div class="target-item">
            <button type="button" class="remove-icon" onclick="removeTarget(this)" aria-label="<?php _e('Remove Target', 'cache_invalidator'); ?>">
                &times;
            </button>
            <select name="cache_invalidator_options[postType][<?php echo esc_attr($postTypeIndex); ?>][targets][<?php echo esc_attr($targetIndex); ?>][type]" onchange="toggleTargetValueInput(this)">
                <option value=""><?php _e('Select type', 'cache_invalidator'); ?></option>
                <option value="template" <?php selected($targetType, 'template'); ?>><?php _e('Template', 'cache_invalidator'); ?></option>
                <option value="gutenberg" <?php selected($targetType, 'gutenberg'); ?>><?php _e('Gutenberg', 'cache_invalidator'); ?></option>
                <option value="home" <?php selected($targetType, 'home'); ?>><?php _e('Home', 'cache_invalidator'); ?></option>
                <option value="layout" <?php selected($targetType, 'layout'); ?>><?php _e('Layout', 'cache_invalidator'); ?></option>
            </select>
            <input type="text" name="cache_invalidator_options[postType][<?php echo esc_attr($postTypeIndex); ?>][targets][<?php echo esc_attr($targetIndex); ?>][value]" placeholder="<?php _e('Target Value', 'cache_invalidator'); ?>" value="<?php echo esc_attr($targetValue); ?>" <?php if (!in_array($targetType, ['template', 'gutenberg'])) echo 'style="display:none;"'; ?>>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueueAdminScripts() {
        wp_enqueue_script('cache-invalidator-admin-script', plugin_dir_url(__FILE__) . 'admin.js', [], null, true);
        wp_enqueue_style('cache-invalidator-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css', [], null);
    }
}

new CacheInvalidatorAdmin();
