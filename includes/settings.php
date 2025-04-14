<?php


if (!function_exists('pfe_check_public_folder')) {
    require_once plugin_dir_path(__FILE__) . '../includes/explorer.php';
}

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

/**
 * Initialize settings for the Public Folder Explorer plugin.
 *
 * This function registers settings, sections, and fields for the plugin's
 * options page. It includes settings for the base directory, allowed file
 * types, hidden folders, hidden files, and frontend title. Each setting is
 * registered with a corresponding sanitize callback to ensure valid input.
 * Additionally, a settings section with relevant fields is added to the
 * WordPress settings API.
 */

function pfe_settings_init() {
    register_setting('pfe_settings', 'pfe_base_dir', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ABSPATH . 'public-files'
    ]);

    register_setting('pfe_settings', 'pfe_allowed_file_types', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,mp3,mp4'
    ]);

    register_setting('pfe_settings', 'pfe_hidden_folders', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);

    register_setting('pfe_settings', 'pfe_hidden_files', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);

    register_setting('pfe_settings', 'pfe_frontend_title', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => __('Public Files Explorer', 'public-folder-explorer')
    ]);

    add_settings_section(
        'pfe_settings_section',
        __('Folder Explorer Settings', 'public-folder-explorer'),
        'pfe_settings_section_callback',
        'pfe_settings'
    );

    add_settings_field(
        'pfe_base_dir',
        __('Base Directory', 'public-folder-explorer'),
        'pfe_base_dir_render',
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

// [Keep all your existing render functions unchanged...]

function pfe_options_page() {
    // Check capabilities first
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    try {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Public Folder Explorer Settings', 'public-folder-explorer'); ?></h1>
            <?php settings_errors(); ?>
            <form action="options.php" method="post">
                <?php
                settings_fields('pfe_settings');
                do_settings_sections('pfe_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    } catch (Exception $e) {
        // Basic fallback if settings can't be loaded
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Public Folder Explorer Settings', 'public-folder-explorer'); ?></h1>
            <div class="notice notice-error">
                <p><?php esc_html_e('The settings interface encountered an error. Using basic configuration form.', 'public-folder-explorer'); ?></p>
            </div>
            <form method="post" action="options.php">
                <?php settings_fields('pfe_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Base Directory', 'public-folder-explorer'); ?></th>
                        <td>
                            <input type="text" name="pfe_base_dir"
                                value="<?php echo esc_attr(get_option('pfe_base_dir')); ?>"
                                class="regular-text">
                        </td>
                    </tr>
                    <!-- Add other fields similarly -->
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
        error_log('PFE Settings Error: ' . $e->getMessage());
    }
}

/**
 * Callback for the settings section. Displays a message with the current status of the public folder.
 *
 * @return void
 */
function pfe_settings_section_callback() {
    echo '<p>' . esc_html__('Configure the Public Folder Explorer settings below.', 'public-folder-explorer') . '</p>';

    // Safe check for folder function
    if (function_exists('pfe_check_public_folder')) {
        $folder_check = pfe_check_public_folder();
        if (is_wp_error($folder_check)) {
            echo '<div class="notice notice-error inline"><p>';
            echo '<strong>' . esc_html__('Warning:', 'public-folder-explorer') . '</strong> ';
            echo esc_html($folder_check->get_error_message());
            echo '</p></div>';
        } else {
            $base_dir = esc_html(get_option('pfe_base_dir'));
            echo '<div class="notice notice-success inline"><p>';
            echo '<strong>' . esc_html__('Folder status:', 'public-folder-explorer') . '</strong> ';
            echo esc_html__('Folder exists at:', 'public-folder-explorer') . ' <code>' . $base_dir . '</code>';

            // Additional permission check
            if (!is_readable($base_dir)) {
                echo '<br><span style="color:red">' . esc_html__('Warning: Directory is not readable!', 'public-folder-explorer') . '</span>';
            }
            echo '</p></div>';
        }
    } else {
        echo '<div class="notice notice-warning inline"><p>';
        echo esc_html__('Folder verification function not available.', 'public-folder-explorer');
        echo '</p></div>';
    }
}