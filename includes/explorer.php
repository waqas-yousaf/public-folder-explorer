<?php
// Handle AJAX requests for folder browsing
add_action('wp_ajax_pfe_browse_folder', 'pfe_browse_folder_callback');
add_action('wp_ajax_nopriv_pfe_browse_folder', 'pfe_browse_folder_callback');

/**
 * Handles AJAX requests for folder browsing
 *
 * Checks if public folder exists and is accessible,
 * then returns a JSON object with the following properties:
 * - name: Folder/file name
 * - type: Folder/file type
 * - path: Relative path to the folder/file
 * - size: File size (empty string for folders)
 * - modified: File modification date (empty string for folders)
 * - url: URL of the file (empty string for folders)
 *
 * Security check is performed to prevent directory traversal.
 */
function pfe_browse_folder_callback() {
    check_ajax_referer('pfe_nonce', 'nonce');

    // Check if public folder exists and is accessible
    $folder_check = pfe_check_public_folder();
    if (is_wp_error($folder_check)) {
        wp_send_json_error($folder_check->get_error_message());
    }

    $base_dir = get_option('pfe_base_directory');


    $base_dir = get_option('pfe_base_directory');
    $allowed_types = get_option('pfe_allowed_file_types');
    $hidden_folders = explode(',', get_option('pfe_hidden_folders'));
    $hidden_files = explode(',', get_option('pfe_hidden_files'));

    $allowed_extensions = array_map('trim', explode(',', $allowed_types));
    $hidden_folders = array_map('trim', $hidden_folders);
    $hidden_files = array_map('trim', $hidden_files);

    $current_path = isset($_POST['path']) ? sanitize_text_field($_POST['path']) : '';
    $full_path = realpath($base_dir . '/' . $current_path);

    // Security check to prevent directory traversal
    if (strpos($full_path, realpath($base_dir)) !== 0) {
        wp_send_json_error(__('Access denied.', 'public-folder-explorer'));
    }

    $items = array();

    if (is_dir($full_path)) {
        $dir_items = scandir($full_path);

        foreach ($dir_items as $item) {
            if ($item == '.' || $item == '..') continue;

            $item_path = $full_path . '/' . $item;
            $relative_path = ltrim($current_path . '/' . $item, '/');

            if (is_dir($item_path)) {
                // Skip hidden folders
                if (in_array($item, $hidden_folders)) continue;

                $items[] = array(
                    'name' => $item,
                    'type' => 'folder',
                    'path' => $relative_path,
                    'size' => '',
                    'modified' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), filemtime($item_path))
                );
            } else {
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));

                // Skip hidden files and not allowed extensions
                if (in_array($item, $hidden_files) || !in_array($ext, $allowed_extensions)) continue;

                $items[] = array(
                    'name' => $item,
                    'type' => 'file',
                    'path' => $relative_path,
                    'size' => pfe_format_filesize(filesize($item_path)),
                    'modified' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), filemtime($item_path)),
                    'url' => pfe_get_file_url($relative_path)
                );
            }
        }
    }

    wp_send_json_success($items);
}

// Handle AJAX requests for file search
add_action('wp_ajax_pfe_search_files', 'pfe_search_files_callback');
add_action('wp_ajax_nopriv_pfe_search_files', 'pfe_search_files_callback');

function pfe_search_files_callback() {
    check_ajax_referer('pfe_nonce', 'nonce');

    $base_dir = get_option('pfe_base_directory');
    $allowed_types = get_option('pfe_allowed_file_types');
    $hidden_folders = explode(',', get_option('pfe_hidden_folders'));
    $hidden_files = explode(',', get_option('pfe_hidden_files'));
    $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $include_folders = isset($_POST['include_folders']) ? (bool)$_POST['include_folders'] : false;

    $allowed_extensions = array_map('trim', explode(',', $allowed_types));
    $hidden_folders = array_map('trim', $hidden_folders);
    $hidden_files = array_map('trim', $hidden_files);

    $results = pfe_recursive_search($base_dir, $search_term, $allowed_extensions, $hidden_folders, $hidden_files, $include_folders);

    wp_send_json_success($results);
}

function pfe_recursive_search($dir, $search_term, $allowed_extensions, $hidden_folders, $hidden_files, $include_folders = false) {
    $results = array();
    $dir_items = scandir($dir);

    foreach ($dir_items as $item) {
        if ($item == '.' || $item == '..') continue;

        $item_path = $dir . '/' . $item;
        $relative_path = str_replace(get_option('pfe_base_directory') . '/', '', $item_path);

        if (is_dir($item_path)) {
            // Skip hidden folders
            if (in_array($item, $hidden_folders)) continue;

            // Check if folder name matches search term
            if ($include_folders && stripos($item, $search_term) !== false) {
                $results[] = array(
                    'name' => $item,
                    'type' => 'folder',
                    'path' => $relative_path,
                    'size' => '',
                    'modified' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), filemtime($item_path)),
                    'url' => '' // Folders don't have URLs
                );
            }

            // Recursively search subdirectories
            $results = array_merge($results, pfe_recursive_search($item_path, $search_term, $allowed_extensions, $hidden_folders, $hidden_files, $include_folders));
        } else {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));

            // Skip hidden files and not allowed extensions
            if (in_array($item, $hidden_files) || !in_array($ext, $allowed_extensions)) continue;

            // Check if filename contains search term (case insensitive)
            if (stripos($item, $search_term) !== false) {
                $results[] = array(
                    'name' => $item,
                    'type' => 'file',
                    'path' => $relative_path,
                    'size' => pfe_format_filesize(filesize($item_path)),
                    'modified' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), filemtime($item_path)),
                    'url' => pfe_get_file_url($relative_path)
                );
            }
        }
    }

    return $results;
}

function pfe_format_filesize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}

function pfe_check_public_folder() {
    $base_dir = get_option('pfe_base_directory');

    // Prevent using directories outside WordPress
    if (strpos(realpath($base_dir), realpath(ABSPATH)) !== 0) {
        return new WP_Error(
            'invalid_location',
            __('The public folder must be inside the WordPress installation directory.', 'public-folder-explorer')
        );
    }

    if (!file_exists($base_dir)) {
        // Attempt to create the folder
        pfe_create_public_folder($base_dir);

        // Check if creation was successful
        if (!file_exists($base_dir)) {
            return new WP_Error(
                'folder_missing',
                sprintf(
                    __('The public files folder does not exist and could not be created. Please create the folder manually at: %s', 'public-folder-explorer'),
                    $base_dir
                )
            );
        }
    }

    if (!is_dir($base_dir)) {
        return new WP_Error(
            'not_a_directory',
            sprintf(
                __('The specified path is not a directory: %s', 'public-folder-explorer'),
                $base_dir
            )
        );
    }

    if (!is_readable($base_dir)) {
        return new WP_Error(
            'not_readable',
            sprintf(
                __('The public files folder is not readable. Please check permissions for: %s', 'public-folder-explorer'),
                $base_dir
            )
        );
    }

    return true;
}

add_action('admin_notices', 'pfe_admin_notices');

function pfe_admin_notices() {
    if (!current_user_can('manage_options')) return;

    $folder_check = pfe_check_public_folder();
    if (is_wp_error($folder_check)) {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Public Folder Explorer:</strong> ' . $folder_check->get_error_message();
        echo '</p></div>';
    }
}



// Add this new function to explorer.php:
function pfe_get_file_url($relative_path) {
    $base_dir = get_option('pfe_base_directory');

    // If files are in WordPress root directory
    if (strpos($base_dir, ABSPATH) !== false) {
        $root_relative = str_replace(ABSPATH, '', $base_dir);
        return site_url('/') . $root_relative . '/' . $relative_path;
    }

    // Fallback to secure download method for other locations
    return add_query_arg([
        'pfe_file' => $relative_path,
        'nonce' => wp_create_nonce('pfe_file_access')
    ], admin_url('admin-ajax.php?action=pfe_serve_file'));
}

// Add this AJAX handler for protected files:
add_action('wp_ajax_pfe_serve_file', 'pfe_serve_file_callback');
add_action('wp_ajax_nopriv_pfe_serve_file', 'pfe_serve_file_callback');

function pfe_serve_file_callback() {
    check_ajax_referer('pfe_file_access', 'nonce');

    $relative_path = isset($_GET['pfe_file']) ? sanitize_text_field($_GET['pfe_file']) : '';
    $base_dir = get_option('pfe_base_directory');
    $file_path = realpath($base_dir . '/' . $relative_path);

    // Enhanced security check
    if (strpos($file_path, realpath($base_dir)) !== 0 ||
        !file_exists($file_path) ||
        !is_file($file_path)) {
        wp_die('File not found', 404);
    }

    // Get MIME type
    $mime_type = mime_content_type($file_path);
    $file_name = basename($file_path);

    // Serve the file
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: inline; filename="' . $file_name . '"');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
}