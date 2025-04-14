<?php
// Register shortcode
add_shortcode('public_folder_explorer', 'pfe_shortcode_callback');

function pfe_shortcode_callback($atts) {
    wp_enqueue_style('pfe-style');
    wp_enqueue_script('pfe-script');

    // $base_dir = get_option('pfe_base_dir');
    // $base_url = content_url('/public-files/');

    ob_start();
    ?>
    <div class="pfe-container">
    <div class="pfe-header">
    <?php
    $title = get_option('pfe_frontend_title', __('Public Files Explorer', 'public-folder-explorer'));
    if($title) {
    ?>
    <h2><?php echo esc_html(); ?></h2>
    <?php
    }
    ?>
    <div class="pfe-header-controls">
        <div class="pfe-search-box">
            <input type="text" id="pfe-search-input" placeholder="<?php _e('Search files and folders...', 'public-folder-explorer'); ?>">
            <button id="pfe-search-button"><?php _e('Search', 'public-folder-explorer'); ?></button>
        </div>
        <div class="pfe-layout-switcher">
            <button class="pfe-layout-btn active" data-layout="list" title="<?php _e('List View', 'public-folder-explorer'); ?>">
                <span class="dashicons dashicons-list-view"></span>
            </button>
            <button class="pfe-layout-btn" data-layout="grid" title="<?php _e('Grid View', 'public-folder-explorer'); ?>">
                <span class="dashicons dashicons-grid-view"></span>
            </button>
        </div>
    </div>
</div>

        <div class="pfe-breadcrumb">
            <span class="pfe-breadcrumb-item" data-path=""><?php _e('Home', 'public-folder-explorer'); ?></span>
        </div>

        <div class="pfe-file-list">
            <div class="pfe-loading"><?php _e('Loading...', 'public-folder-explorer'); ?></div>
        </div>

        <div class="pfe-search-results" style="display: none;">
            <div class="pfe-results-header"></div>
            <div class="pfe-results-list"></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}