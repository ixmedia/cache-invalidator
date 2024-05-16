<?php
// Ajouter un menu de paramètres
add_action('admin_menu', 'mon_plugin_add_admin_menu');

function mon_plugin_add_admin_menu() {
    add_options_page(
        'Cache invalidation Settings',
        'Cache Invalidation',
        'manage_options',
        'mon_plugin',
        'mon_plugin_options_page'
    );
}
// Afficher la page de paramètres
function mon_plugin_options_page() {
  ?>
  <div class="wrap">
      <h1>Mon Plugin Settings</h1>
      <form action="options.php" method="post">
          <?php
          settings_fields('mon_plugin_options');
          do_settings_sections('mon_plugin');
          submit_button();
          ?>
      </form>
  </div>
  <?php
}
// Enregistrer les paramètres
add_action('admin_init', 'mon_plugin_settings_init');

function mon_plugin_settings_init() {
    register_setting('mon_plugin_options', 'mon_plugin_options');

    // Section pour les triggers de type postType
    add_settings_section(
        'mon_plugin_post_type_section',
        __('Post Type Triggers', 'mon_plugin'),
        'mon_plugin_post_type_section_callback',
        'mon_plugin'
    );

    add_settings_field(
        'mon_plugin_post_type_triggers',
        __('Post Type Triggers', 'mon_plugin'),
        'mon_plugin_post_type_triggers_render',
        'mon_plugin',
        'mon_plugin_post_type_section'
    );

    // Section pour les triggers de type dateField
    add_settings_section(
        'mon_plugin_date_field_section',
        __('Date Field Triggers', 'mon_plugin'),
        'mon_plugin_date_field_section_callback',
        'mon_plugin'
    );

    add_settings_field(
        'mon_plugin_date_field_triggers',
        __('Date Field Triggers', 'mon_plugin'),
        'mon_plugin_date_field_triggers_render',
        'mon_plugin',
        'mon_plugin_date_field_section'
    );
}

function mon_plugin_post_type_section_callback() {
    echo __('Configure the triggers for specific post types.', 'mon_plugin');
}

function mon_plugin_date_field_section_callback() {
    echo __('Configure the triggers for date fields in post types.', 'mon_plugin');
}

function mon_plugin_post_type_triggers_render() {
    $options = get_option('mon_plugin_options');
    $postTypeTriggers = isset($options['postType']) ? $options['postType'] : [];
    ?>
    <div id="postTypeTriggersRepeater">
        <button type="button" onclick="addPostTypeTrigger()">Add Post Type Trigger</button>
        <?php foreach ($postTypeTriggers as $postType => $settings): ?>
            <div class="repeater-item">
                <input type="text" name="mon_plugin_options[postType][<?php echo esc_attr($postType); ?>][type]" value="<?php echo esc_attr($settings['type']); ?>" placeholder="Post Type" />
                <textarea name="mon_plugin_options[postType][<?php echo esc_attr($postType); ?>][targets]" placeholder="Targets"><?php echo esc_textarea(json_encode($settings['targets'], JSON_PRETTY_PRINT)); ?></textarea>
                <button type="button" onclick="removePostTypeTrigger(this)">Remove</button>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function mon_plugin_date_field_triggers_render() {
    $options = get_option('mon_plugin_options');
    $dateFieldTriggers = isset($options['dateField']) ? $options['dateField'] : [];
    ?>
    <div id="dateFieldTriggersRepeater">
        <button type="button" onclick="addDateFieldTrigger()">Add Date Field Trigger</button>
        <?php foreach ($dateFieldTriggers as $postType => $settings): ?>
            <div class="repeater-item">
                <input type="text" name="mon_plugin_options[dateField][<?php echo esc_attr($postType); ?>][fieldNames]" value="<?php echo esc_attr(implode(',', $settings['fieldNames'])); ?>" placeholder="Field Names (comma separated)" />
                <textarea name="mon_plugin_options[dateField][<?php echo esc_attr($postType); ?>][targets]" placeholder="Targets"><?php echo esc_textarea(json_encode($settings['targets'], JSON_PRETTY_PRINT)); ?></textarea>
                <button type="button" onclick="removeDateFieldTrigger(this)">Remove</button>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

add_action('admin_enqueue_scripts', 'mon_plugin_enqueue_admin_scripts');

function mon_plugin_enqueue_admin_scripts() {
    wp_enqueue_script('mon-plugin-admin-script', plugin_dir_url(__FILE__) . 'admin.js', [], null, true);
}

$options = get_option('mon_plugin_options');

// var_dump($options);
// die;
