<?php
class CacheInvalidatorAdmin {
    private $optionName = 'cache_invalidator_options';

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
                settings_fields($this->optionName);
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
        register_setting($this->optionName, $this->optionName);

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
        $post_types = get_post_types([], 'objects');

        // Get the saved options
        $options = get_option($this->optionName);
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
     * Callback for the taxonomy section
     */
    public function taxonomySectionCallback() {
        echo __('Configure triggers for specific taxonomies.', 'cache_invalidator');
    }

    /**
     * Render the taxonomy triggers field
     */
    public function taxonomyTriggersRender() {
        // Get all registered taxonomies
        $taxonomies = get_taxonomies([], 'objects');

        // Get the saved options
        $options = get_option($this->optionName);
        $taxonomyTriggers = isset($options['taxonomy']) ? $options['taxonomy'] : [];
        ?>
        <div id="taxonomyTriggersRepeater" data-template="<?php echo htmlspecialchars($this->getTaxonomyTriggerHtml('__index__', null, $taxonomies)); ?>">
            <button type="button" id="addTaxonomyTrigger" class="button button-primary"><?php _e('Add Taxonomy Trigger', 'cache_invalidator'); ?></button>
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
        ob_start();
        ?>
        <div class="repeater-item">
            <select name="<?php echo $this->optionName; ?>[postType][<?php echo esc_attr($index); ?>][type]" onchange="toggleTargetFields(this)">
                <option value=""><?php _e('Select post type', 'cache_invalidator'); ?></option>
                <?php foreach ($post_types as $type): ?>
                    <option value="<?php echo esc_attr($type->name); ?>" <?php selected($typeValue, $type->name); ?>>
                        <?php echo esc_html($type->label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="target-repeater" style="<?php echo empty($typeValue) ? 'display:none;' : ''; ?>">
                <button type="button" class="button add-target"><?php _e('Add Target', 'cache_invalidator'); ?></button>
                <div class="target-items" data-template="<?php echo htmlspecialchars($this->getTargetHtml($index, 'postType', '__target_index__')); ?>">
                    <?php foreach ($targets as $targetIndex => $target): ?>
                        <?php echo $this->getTargetHtml($index, 'postType', $targetIndex, $target); ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="button" class="button-link delete" onclick="removePostTypeTrigger(this)"><?php _e('Remove Post Type Trigger', 'cache_invalidator'); ?></button>
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
            <select name="<?php echo $this->optionName; ?>[taxonomy][<?php echo esc_attr($index); ?>][type]" onchange="toggleTargetFields(this)">
                <option value=""><?php _e('Select taxonomy', 'cache_invalidator'); ?></option>
                <?php foreach ($taxonomies as $taxonomy): ?>
                    <option value="<?php echo esc_attr($taxonomy->name); ?>" <?php selected($typeValue, $taxonomy->name); ?>>
                        <?php echo esc_html($taxonomy->label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="target-repeater" style="<?php echo empty($typeValue) ? 'display:none;' : ''; ?>">
                <button type="button" class="button add-target"><?php _e('Add Target', 'cache_invalidator'); ?></button>
                <div class="target-items" data-template="<?php echo htmlspecialchars($this->getTargetHtml($index, 'taxonomy', '__target_index__')); ?>">
                    <?php foreach ($targets as $targetIndex => $target): ?>
                        <?php echo $this->getTargetHtml($index, 'taxonomy', $targetIndex, $target); ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="button" class="button-link delete" onclick="removeTaxonomyTrigger(this)"><?php _e('Remove Taxonomy Trigger', 'cache_invalidator'); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate HTML for targets
     *
     * @param string $elementIndex The index of the post type trigger
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
            <button type="button" class="remove-icon" onclick="removeTarget(this)" aria-label="<?php _e('Remove Target', 'cache_invalidator'); ?>">
                &times;
            </button>
            <select name="<?php echo $this->optionName; ?>[<?php echo $elementType?>][<?php echo esc_attr($elementIndex); ?>][targets][<?php echo esc_attr($targetIndex); ?>][type]" onchange="toggleTargetValueInput(this)">
                <option value=""><?php _e('Select type', 'cache_invalidator'); ?></option>
                <option value="template" <?php selected($targetType, 'template'); ?>><?php _e('Template', 'cache_invalidator'); ?></option>
                <option value="gutenberg" <?php selected($targetType, 'gutenberg'); ?>><?php _e('Gutenberg', 'cache_invalidator'); ?></option>
                <option value="home" <?php selected($targetType, 'home'); ?>><?php _e('Home', 'cache_invalidator'); ?></option>
                <option value="layout" <?php selected($targetType, 'layout'); ?>><?php _e('Layout', 'cache_invalidator'); ?></option>
            </select>
            <input type="text" name="<?php echo $this->optionName; ?>[<?php echo $elementType?>][<?php echo esc_attr($elementIndex); ?>][targets][<?php echo esc_attr($targetIndex); ?>][value]" placeholder="<?php _e('Target Value', 'cache_invalidator'); ?>" value="<?php echo esc_attr($targetValue); ?>" <?php if (!in_array($targetType, ['template', 'gutenberg'])) echo 'style="display:none;"'; ?>>
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
