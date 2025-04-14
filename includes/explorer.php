<?php
// Helper function to format file size
if (!function_exists('pfe_format_filesize')) {
    function pfe_format_filesize($bytes) {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes == 1) {
            return $bytes . ' byte';
        } else {
            return '0 bytes';
        }
    }
}

// Helper function to get the file URL
if (!function_exists('pfe_get_file_url')) {
    function pfe_get_file_url($relative_path) {
        $base_url = content_url(); // Get the content directory URL
        $upload_dir = wp_upload_dir();
        $base_path = realpath(get_option('pfe_base_dir'));
        $file_path = realpath(get_option('pfe_base_dir') . '/' . $relative_path);

        if ($base_path && $file_path && strpos($file_path, $base_path) === 0) {
            $uri = str_replace(ABSPATH, '/', $file_path);
            return site_url($uri);
        } else {
            // Fallback: Try to construct URL based on content directory
            return $base_url . '/uploads/' . str_replace(basename($upload_dir['basedir']) . '/', '', $relative_path);
        }
    }
}

if (!function_exists('pfe_check_public_folder')) {
    function pfe_check_public_folder() {
        $base_dir = get_option('pfe_base_dir');
        if (empty($base_dir)) {
            return new WP_Error('pfe_no_base_dir', __('Base directory not set.', 'public-folder-explorer'));
        }
        if (!is_dir($base_dir)) {
            return new WP_Error('pfe_invalid_dir', sprintf(__('Directory "%s" does not exist.', 'public-folder-explorer'), esc_html($base_dir)));
        }
        if (!is_readable($base_dir)) {
            return new WP_Error('pfe_not_readable', sprintf(__('Directory "%s" is not readable.', 'public-folder-explorer'), esc_html($base_dir)));
        }
        return true; // Or some other success indicator
    }
}

// Handle AJAX requests for folder browsing
add_action('wp_ajax_pfe_browse_folder', 'pfe_browse_folder_callback');
add_action('wp_ajax_nopriv_pfe_browse_folder', 'pfe_browse_folder_callback');

function pfe_browse_folder_callback() {
    try {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pfe_nonce')) {
            throw new Exception(__('Security verification failed', 'public-folder-explorer'));
        }

        $base_dir = get_option('pfe_base_dir');
        if (empty($base_dir)) {
            throw new Exception(__('Base directory not configured', 'public-folder-explorer'));
        }

        $current_path = sanitize_text_field($_POST['path'] ?? '');
        $full_path = realpath($base_dir . '/' . $current_path);

        // Security check to prevent directory traversal
        if (strpos($full_path, realpath($base_dir)) !== 0) {
            throw new Exception(__('Access denied.', 'public-folder-explorer'));
        }

        if (!is_dir($full_path) || !is_readable($full_path)) {
            throw new Exception(__('Invalid directory or unable to read.', 'public-folder-explorer'));
        }

        $items = scandir($full_path);
        $output = array();
        $allowed_extensions = array_map('trim', explode(',', get_option('pfe_allowed_file_types') ?: ''));
        $hidden_folders = array_map('trim', explode(',', get_option('pfe_hidden_folders') ?: ''));
        $hidden_files = array_map('trim', explode(',', get_option('pfe_hidden_files') ?: ''));

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $item_path = $full_path . '/' . $item;
            $relative_path = ltrim(str_replace(realpath($base_dir), '', realpath($item_path)), '/');

            if (is_dir($item_path) && !in_array($item, $hidden_folders)) {
                $output[] = array(
                    'name' => $item,
                    'type' => 'folder',
                    'path' => $relative_path,
                    'size' => '',
                    'modified' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), filemtime($item_path)),
                    'url' => ''
                );
            } elseif (is_file($item_path) && !in_array($item, $hidden_files)) {
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                if (empty($allowed_extensions) || in_array($ext, $allowed_extensions)) {
                    $output[] = array(
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

        wp_send_json_success($output);

    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}

// Handle AJAX requests for file searching
add_action('wp_ajax_pfe_search_files', 'pfe_search_files_callback');
add_action('wp_ajax_nopriv_pfe_search_files', 'pfe_search_files_callback');

function pfe_search_files_callback() {
    try {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pfe_nonce')) {
            throw new Exception(__('Security verification failed', 'public-folder-explorer'));
        }

        $base_dir = get_option('pfe_base_dir');
        if (empty($base_dir)) {
            throw new Exception(__('Base directory not configured', 'public-folder-explorer'));
        }

        $search_term = sanitize_text_field($_POST['search'] ?? '');
        if (empty($search_term)) {
            throw new Exception(__('Search term cannot be empty', 'public-folder-explorer'));
        }

        $current_path = sanitize_text_field($_POST['path'] ?? '');
        $full_path = realpath($base_dir . '/' . $current_path);

        // Security check to prevent directory traversal
        if (strpos($full_path, realpath($base_dir)) !== 0) {
            throw new Exception(__('Access denied.', 'public-folder-explorer'));
        }

        $allowed_types = get_option('pfe_allowed_file_types');
        $hidden_folders = array_map('trim', explode(',', get_option('pfe_hidden_folders') ?: ''));
        $hidden_files = array_map('trim', explode(',', get_option('pfe_hidden_files') ?: ''));
        $allowed_extensions = array_map('trim', explode(',', $allowed_types ?: ''));

        // Perform recursive search
        $results = pfe_recursive_search(
            $full_path,
            $search_term,
            $allowed_extensions,
            $hidden_folders,
            $hidden_files,
            true // Include folders in search results
        );

        wp_send_json_success($results);

    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}

/**
 * Recursively searches through directories for matching files/folders.
 */
function pfe_recursive_search($dir, $search_term, $allowed_extensions, $hidden_folders, $hidden_files, $include_folders = false) {
    $results = array();

    if (!is_dir($dir) || !is_readable($dir)) {
        return $results;
    }

    $items = scandir($dir);
    $base_dir = get_option('pfe_base_dir');

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $item_path = $dir . '/' . $item;
        $relative_path = ltrim(str_replace(realpath($base_dir), '', realpath($item_path)), '/');

        if (is_dir($item_path)) {
            if (in_array($item, $hidden_folders)) {
                continue;
            }
            if ($include_folders && stripos($item, $search_term) !== false) {
                $results[] = array(
                    'name' => $item,
                    'type' => 'folder',
                    'path' => $relative_path,
                    'size' => '',
                    'modified' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), filemtime($item_path)),
                    'url' => ''
                );
            }
            $results = array_merge(
                $results,
                pfe_recursive_search($item_path, $search_term, $allowed_extensions, $hidden_folders, $hidden_files, $include_folders)
            );
        } else {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($item, $hidden_files) || (!empty($allowed_extensions) && !in_array($ext, $allowed_extensions))) {
                continue;
            }
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