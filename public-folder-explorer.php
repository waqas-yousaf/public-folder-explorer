<?php
/*
Plugin Name: Frontend Folder Explorer
Plugin URI: https://waqasmirza.com/public-folder-explorer
Description: Allows visitors to browse and download files from a specified public folder on your server. Use shortcode [public_folder_explorer] to display the file explorer on any page or post.
Version: 0.2.11
Author: Waqas Yousaf
Author URI: https://waqasmirza.com
License: GPL2
*/

defined('ABSPATH') or die('Direct access not allowed.');

// Define constants
define('PFE_VERSION', '0.2.10');
define('PFE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PFE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once PFE_PLUGIN_DIR . 'includes/settings.php';
require_once PFE_PLUGIN_DIR . 'includes/explorer.php';
require_once PFE_PLUGIN_DIR . 'includes/shortcode.php';

// Register activation/deactivation hooks
register_activation_hook(__FILE__, 'pfe_activate_plugin');
register_deactivation_hook(__FILE__, 'pfe_deactivate_plugin');

/**
 * Initialize the plugin.
 */
function pfe_init_plugin() {
    // Load text domain for translations
    load_plugin_textdomain('public-folder-explorer', false, dirname(plugin_basename(__FILE__)) . '/languages/');

    // Add AJAX headers
    add_action('init', 'pfe_add_ajax_headers');

    // Check server configuration
    add_action('admin_init', 'pfe_check_server_config');

    // Enqueue scripts and styles
    add_action('wp_enqueue_scripts', 'pfe_enqueue_scripts');

    // Add allowed origins (consider only if needed for specific cross-origin scenarios)
    // add_filter('allowed_http_origins', 'pfe_add_allowed_origins');
}
add_action('plugins_loaded', 'pfe_init_plugin');

/**
 * Add CORS headers for AJAX requests (only for same-site).
 */
function pfe_add_ajax_headers() {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Access-Control-Allow-Origin: ' . site_url());
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS'); // Specify allowed methods
        header('Access-Control-Allow-Headers: X-Requested-With, Content-Type'); // Specify allowed headers
    }
}

/**
 * Add allowed HTTP origins (consider if strictly necessary).
 */
// function pfe_add_allowed_origins($origins) {
//     $origins[] = site_url();
//     $origins[] = home_url();
//     return $origins;
// }

/**
 * Check server configuration and display admin notice for non-standard servers.
 */
function pfe_check_server_config() {
    if (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') === false &&
        strpos($_SERVER['SERVER_SOFTWARE'], 'LiteSpeed') === false) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>Public Folder Explorer:</strong> ';
            echo esc_html('Your server is running ' . $_SERVER['SERVER_SOFTWARE'] . '. ');
            echo esc_html('Some security features might need manual configuration.');
            echo '</p></div>';
        });
    }
}

/**
 * Plugin activation hook.
 */
function pfe_activate_plugin() {
    try {
        // Set default options if not exists
        if (!get_option('pfe_base_dir')) {
            $default_path = ABSPATH . 'public-files';
            if (!pfe_create_public_folder($default_path)) {
                throw new Exception('Failed to create public folder');
            }
            update_option('pfe_base_dir', $default_path);
        }
        if (!get_option('pfe_allowed_file_types')) {
            update_option('pfe_allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,mp3,mp4');
        }
        if (!get_option('pfe_hidden_folders')) {
            update_option('pfe_hidden_folders', ''); // Initialize if not set
        }
        if (!get_option('pfe_hidden_files')) {
            update_option('pfe_hidden_files', ''); // Initialize if not set
        }
        if (!get_option('pfe_frontend_title')) {
            update_option('pfe_frontend_title', __('Public Files Explorer', 'public-folder-explorer'));
        }
    } catch (Exception $e) {
        error_log('PFE Activation Error: ' . $e->getMessage());
        // Consider displaying an admin notice about the activation failure
    }
}

/**
 * Create public folder with security files.
 */
function pfe_create_public_folder($path) {
    if (!file_exists($path)) {
        if (!wp_mkdir_p($path)) {
            return false;
        }
        $index = $path . '/index.php';
        if (!file_exists($index)) {
            file_put_contents($index, "<?php\n// Silence is golden");
        }
        $sample = $path . '/sample.txt';
        if (!file_exists($sample)) {
            file_put_contents($sample, "Hello World!\nThis is a sample file.");
        }
        return true;
    }
    return true;
}

/**
 * Plugin deactivation hook.
 */
function pfe_deactivate_plugin() {
    // Clean up if needed
    // Consider removing: delete_option('pfe_base_dir');
}

/**
 * Enqueue scripts and styles.
 */
function pfe_enqueue_scripts() {
    wp_enqueue_style('pfe-style', PFE_PLUGIN_URL . 'assets/css/style.css', array(), PFE_VERSION);
    wp_enqueue_script('pfe-script', PFE_PLUGIN_URL . 'assets/js/script.js', array(), PFE_VERSION, true); // No jQuery dependency now

    wp_localize_script('pfe-script', 'pfe_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pfe_nonce'),
        'user_id' => get_current_user_id() // For debugging
    ));
}

// AJAX action to refresh nonce (if you implement it)
add_action('wp_ajax_pfe_refresh_nonce', 'pfe_refresh_nonce_callback');
add_action('wp_ajax_nopriv_pfe_refresh_nonce', 'pfe_refresh_nonce_callback');
function pfe_refresh_nonce_callback() {
    wp_send_json_success(array(
        'nonce' => wp_create_nonce('pfe_nonce')
    ));
}

// Debugging hooks (remove in production)
// add_action('admin_init', 'pfe_debug_settings_page');
// function pfe_debug_settings_page() { ... }
// add_action('admin_footer', 'pfe_nonce_debug');
// function pfe_nonce_debug() { ... }