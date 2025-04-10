<?php
// Add settings page to WordPress admin
add_action('admin_menu', 'pfe_add_admin_menu');
add_action('admin_init', 'pfe_settings_init');

function pfe_add_admin_menu() {
    add_options_page(
        'Public Folder Explorer',
        'Folder Explorer',
        'manage_options',
        'public-folder-explorer',
        'pfe_options_page'
    );
}

function pfe_settings_init() {
    register_setting('pfe_settings', 'pfe_base_directory');
    register_setting('pfe_settings', 'pfe_allowed_file_types');
    register_setting('pfe_settings', 'pfe_hidden_folders');
    register_setting('pfe_settings', 'pfe_hidden_files');
    register_setting('pfe_settings', 'pfe_frontend_title');
    add_settings_section(
        'pfe_settings_section',
        __('Folder Explorer Settings', 'public-folder-explorer'),
        'pfe_settings_section_callback',
        'pfe_settings'
    );

    add_settings_field(
        'pfe_base_directory',
        __('Base Directory', 'public-folder-explorer'),
        'pfe_base_directory_render',
        'pfe_settings',
        'pfe_settings_section'
    );

    add_settings_field(
        'pfe_allowed_file_types',
        __('Allowed File Types', 'public-folder-explorer'),
        'pfe_allowed_file_types_render',
        'pfe_settings',
        'pfe_settings_section'
    );

    add_settings_field(
        'pfe_hidden_folders',
        __('Hidden Folders', 'public-folder-explorer'),
        'pfe_hidden_folders_render',
        'pfe_settings',
        'pfe_settings_section'
    );

    add_settings_field(
        'pfe_hidden_files',
        __('Hidden Files', 'public-folder-explorer'),
        'pfe_hidden_files_render',
        'pfe_settings',
        'pfe_settings_section'
    );
    add_settings_field(
        'pfe_frontend_title',
        __('Frontend Title', 'public-folder-explorer'),
        'pfe_frontend_title_render',
        'pfe_settings',
        'pfe_settings_section'
    );
}

function pfe_base_directory_render() {
    $options = get_option('pfe_base_directory');
    ?>
    <input type="text" name="pfe_base_directory" value="<?php echo esc_attr($options); ?>" style="width: 100%;">
    <p class="description">
        <?php _e('Absolute server path to the public folder. Default:', 'public-folder-explorer'); ?>
        <code><?php echo ABSPATH . 'public-files'; ?></code>
    </p>
    <?php
}

function pfe_allowed_file_types_render() {
    $options = get_option('pfe_allowed_file_types');
    ?>
    <input type="text" name="pfe_allowed_file_types" value="<?php echo esc_attr($options); ?>" style="width: 100%;">
    <p class="description"><?php _e('Comma-separated list of allowed file extensions (without dots).', 'public-folder-explorer'); ?></p>
    <?php
}

function pfe_hidden_folders_render() {
    $options = get_option('pfe_hidden_folders');
    ?>
    <input type="text" name="pfe_hidden_folders" value="<?php echo esc_attr($options); ?>" style="width: 100%;">
    <p class="description"><?php _e('Comma-separated list of folders to hide from browsing.', 'public-folder-explorer'); ?></p>
    <?php
}

function pfe_hidden_files_render() {
    $options = get_option('pfe_hidden_files');
    ?>
    <input type="text" name="pfe_hidden_files" value="<?php echo esc_attr($options); ?>" style="width: 100%;">
    <p class="description"><?php _e('Comma-separated list of files to hide from browsing.', 'public-folder-explorer'); ?></p>
    <?php
}


function pfe_options_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Public Folder Explorer Settings', 'public-folder-explorer'); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('pfe_settings');
            do_settings_sections('pfe_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}


function pfe_settings_section_callback() {
    echo __('Configure the Public Folder Explorer settings below.', 'public-folder-explorer');

    $folder_check = pfe_check_public_folder();
    if (is_wp_error($folder_check)) {
        echo '<div class="notice notice-error inline"><p>';
        echo '<strong>' . __('Warning:', 'public-folder-explorer') . '</strong> ' . $folder_check->get_error_message();
        echo '</p></div>';
    } else {
        $base_dir = get_option('pfe_base_directory');
        echo '<div class="notice notice-success inline"><p>';
        echo '<strong>' . __('Folder status:', 'public-folder-explorer') . '</strong> ' . __('Folder exists and is accessible at:', 'public-folder-explorer') . ' <code>' . esc_html($base_dir) . '</code>';
        echo '</p></div>';
    }
}

/**
 * Renders the frontend title setting field
 *
 * @since 1.0
 */
function pfe_frontend_title_render() {
    $title = get_option('pfe_frontend_title', __('Public Files Explorer', 'public-folder-explorer'));
    ?>
    <input type="text" name="pfe_frontend_title" value="<?php echo esc_attr($title); ?>" style="width: 100%;">
    <p class="description"><?php _e('Title displayed on the frontend explorer.', 'public-folder-explorer'); ?></p>
    <?php
}