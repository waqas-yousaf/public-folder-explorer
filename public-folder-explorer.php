<?php
/*
Plugin Name: Frontend Folder Explorer
Plugin URI: https://waqasmirza.com/public-folder-explorer
Description: Allows visitors to browse and download files from a specified public folder on your server. User shortcode [public_folder_explorer] to display the file explorer on any page or post.
Version: 0.2.7
Author: Waqas Yousaf
Author URI: https://waqasmirza.com
License: GPL2
*/


defined('ABSPATH') or die('Direct access not allowed.');

// Define constants
define('PFE_VERSION', '0.2.7');
define('PFE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PFE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once PFE_PLUGIN_DIR . 'includes/settings.php';
require_once PFE_PLUGIN_DIR . 'includes/explorer.php';
require_once PFE_PLUGIN_DIR . 'includes/shortcode.php';

// Register activation/deactivation hooks
register_activation_hook(__FILE__, 'pfe_activate_plugin');
register_deactivation_hook(__FILE__, 'pfe_deactivate_plugin');

function pfe_activate_plugin() {
    // Set default options if not exists
    if (!get_option('pfe_base_directory')) {
        $default_path = ABSPATH . 'public-files'; // Root directory


        // Create directory if it doesn't exist
        pfe_create_public_folder($default_path);

        update_option('pfe_base_directory', $default_path);
    }

    if (!get_option('pfe_allowed_file_types')) {
        update_option('pfe_allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,mp3,mp4');
    }
}

/**
 * Create public folder with .htaccess protection
 */
function pfe_create_public_folder($path) {
    if (!file_exists($path)) {
        // Create the directory with proper permissions
        if (!wp_mkdir_p($path)) {
            return false;
        }

        // Add security files
        $htaccess = $path . '/.htaccess';
        if (!file_exists($htaccess) && strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false) {
            // file_put_contents($htaccess, "Options -Indexes\nDeny from all");
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

function pfe_deactivate_plugin() {
    // Clean up if needed
}

// Initialize plugin
function pfe_init_plugin() {
    // Load text domain for translations
    load_plugin_textdomain('public-folder-explorer', false, dirname(plugin_basename(__FILE__)) . '/languages/');

    // Enqueue scripts and styles
    add_action('wp_enqueue_scripts', 'pfe_enqueue_scripts');
}

function pfe_enqueue_scripts() {
    wp_enqueue_style('pfe-style', PFE_PLUGIN_URL . 'assets/css/style.css', array(), PFE_VERSION);
    wp_enqueue_script('pfe-script', PFE_PLUGIN_URL . 'assets/js/script.js', array('jquery'), PFE_VERSION, true);

    wp_localize_script('pfe-script', 'pfe_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pfe_nonce')
    ));
}

add_action('plugins_loaded', 'pfe_init_plugin');