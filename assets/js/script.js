jQuery(document).ready(function($) {
    var currentPath = '';
    var searchActive = false;
    var currentLayout = 'list'; // Default layout
    var savedLayout = localStorage.getItem('pfe_layout_preference');
    if (savedLayout) {
        currentLayout = savedLayout;
        $('.pfe-layout-btn').removeClass('active');
        $('.pfe-layout-btn[data-layout="' + currentLayout + '"]').addClass('active');
        $('.pfe-file-list').addClass(currentLayout + '-view');
    }

    $(document).on('click', '.pfe-layout-btn', function() {
        $('.pfe-layout-btn').removeClass('active');
        $(this).addClass('active');
        currentLayout = $(this).data('layout');
        $('.pfe-file-list').removeClass('list-view grid-view').addClass(currentLayout + '-view');

        // Store preference in localStorage
        localStorage.setItem('pfe_layout_preference', currentLayout);
    });


    // Load initial directory
    loadDirectory('');
    // Handle file item clicks
    $(document).on('click', '.pfe-go-up-item', function() {
        var path = $(this).data('path');
        currentPath = path; // Update currentPath immediately
        loadDirectory(path);
    });
    // Handle folder clicks
    $(document).on('click', '.pfe-folder-item', function() {
        var path = $(this).data('path');
        currentPath = path; // Update currentPath immediately
        loadDirectory(path);
    });

    // Handle breadcrumb clicks
    $(document).on('click', '.pfe-breadcrumb-item', function() {
        var path = $(this).data('path');
        loadDirectory(path);
    });

    // Handle search button click
    $('#pfe-search-button').click(function() {
        var searchTerm = $('#pfe-search-input').val().trim();
        if (searchTerm.length > 0) {
            searchFiles(searchTerm);
        }
    });

    // Handle Enter key in search input
    $('#pfe-search-input').keypress(function(e) {
        if (e.which == 13) {
            var searchTerm = $(this).val().trim();
            if (searchTerm.length > 0) {
                searchFiles(searchTerm);
            }
        }
    });

    // Handle back to browse button
    $(document).on('click', '.pfe-back-to-browse', function() {
        $('.pfe-search-results').hide();
        $('.pfe-file-list').show();
        searchActive = false;
        updateBreadcrumb(currentPath);
    });

    function loadDirectory(path) {
        currentPath = path;
        $('.pfe-file-list').html('<div class="pfe-loading">Loading...</div>');

        $.ajax({
            url: pfe_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'pfe_browse_folder',
                path: path,
                nonce: pfe_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderDirectory(response.data);
                    updateBreadcrumb(path);
                } else {
                    $('.pfe-file-list').html('<div class="pfe-error">' + response.data + '</div>');
                }
            },
            error: function() {
                $('.pfe-file-list').html('<div class="pfe-error">Error loading directory.</div>');
            }
        });
    }

/**
 * Renders the directory view by populating the file list with folders and files.
 *
 * @param {Array} items - Array of items to render, each item is an object containing
 *                        properties such as 'type', 'path', 'name', 'modified', 'size', and 'url'.
 *
 * If the directory is empty and at the root, a message is displayed indicating that the folder
 * is empty. If not in the root directory, a "Go Up" button is added to navigate to the parent
 * directory. Items are sorted to display folders first, followed by files. Each item is rendered
 * with relevant details and a download button for files.
 */

    function renderDirectory(items) {
        if (items.length === 0 && currentPath === '') {
            $('.pfe-file-list').html('<div class="pfe-empty">This folder is empty</div>');
            return;
        }

        var html = '';

        // Add "Go Up" button if not in root
        if (currentPath !== '') {
            var parentPath = currentPath.split('/').slice(0, -1).join('/');
            html += `
            <div class="pfe-go-up-item" data-path="${parentPath}">
                <span class="pfe-go-up-icon dashicons dashicons-arrow-up-alt"></span>
                <div class="pfe-item-content">
                    <span class="pfe-go-up-text">Go Up</span>
                </div>
            </div>
        `;
        }

        // Sort folders first, then files
        items.sort(function(a, b) {
            if (a.type === 'folder' && b.type !== 'folder') return -1;
            if (a.type !== 'folder' && b.type === 'folder') return 1;
            return a.name.localeCompare(b.name);
        });

        items.forEach(function(item) {
            if (item.type === 'folder') {
                html += `
                <div class="pfe-folder-item" data-path="${item.path}">
                    <span class="pfe-folder-icon dashicons dashicons-category"></span>
                    <div class="pfe-item-content">
                        <span class="pfe-file-name">${item.name}</span>
                    </div>
                    <div class="pfe-file-info">
                        <span class="pfe-file-modified">${item.modified}</span>
                    </div>
                </div>
            `;
            } else {
                html += `
                <div class="pfe-file-item">
                    <span class="pfe-file-icon dashicons dashicons-media-default"></span>
                    <div class="pfe-item-content">
                        <span class="pfe-file-name">${item.name}</span>
                    </div>
                    <div class="pfe-file-info">
                        <span class="pfe-file-size">${item.size}</span>
                        <span class="pfe-file-modified">${item.modified}</span>
                        <div class="pfe-file-actions">
                            <a href="${item.url}" target="_blank" class="pfe-preview-button" title="Preview">
                                Preview
                            </a>
                            <a href="${item.url}" download class="pfe-download-button" title="Download">
                             Download
                            </a>
                        </div>
                    </div>
                </div>
            `;
            }
        });

        $('.pfe-file-list').html(html);
    }

    function updateBreadcrumb(path) {
        if (searchActive) return;

        var parts = path.split('/').filter(Boolean);
        var breadcrumbHtml = '<span class="pfe-breadcrumb-item" data-path="">Root</span>';

        if (parts.length > 0) {
            var currentPath = '';
            parts.forEach(function(part, index) {
                currentPath += (currentPath ? '/' : '') + part;
                breadcrumbHtml += `<span class="pfe-breadcrumb-item" data-path="${currentPath}">${part}</span>`;
            });
        }

        $('.pfe-breadcrumb').html(breadcrumbHtml);
    }

// Update the searchFiles function
function searchFiles(term) {
    var searchFromPath = currentPath;


    $('.pfe-file-list').hide();
    $('.pfe-search-results .pfe-results-list').html('<div class="pfe-loading">Searching...></div>');

    // Update search results header with breadcrumb
    $('.pfe-search-results .pfe-results-header').html(`
        <div class="pfe-search-breadcrumb">
            <span class="pfe-search-location">
                Search results in:
                ${searchFromPath ? searchFromPath : 'Home'}
            </span>
            <button class="pfe-back-to-browse" data-path="${searchFromPath}">
                Back to Browsing
            </button>
        </div>
        <h3>Search Results</h3>
    `);

    $('.pfe-search-results').show();
    searchActive = true;

    $.ajax({
        url: pfe_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'pfe_search_files',
            search: term,
            include_folders: true, // Add this parameter
            nonce: pfe_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                renderSearchResults(response.data, term);
            } else {
                $('.pfe-search-results .pfe-results-list').html('<div class="pfe-error">' + response.data + '</div>');
            }
        },
        error: function() {
            $('.pfe-search-results .pfe-results-list').html('<div class="pfe-error">Error searching files.</div>');
        }
    });
}

// Update renderSearchResults to handle folders
function renderSearchResults(items, term) {
    if (items.length === 0) {
        $('.pfe-search-results .pfe-results-list').html(
            `<div class="pfe-empty">No items found for "${term}"</div>`
        );
        return;
    }

    var html = `<div class="pfe-search-summary">Found ${items.length} items matching "${term}"</div>`;

    items.forEach(function(item) {
        var folderPath = item.path.split('/').slice(0, -1).join(' / ');

        if (item.type === 'folder') {
            html += `
                <div class="pfe-folder-item" data-path="${item.path}">
                    <span class="pfe-folder-icon dashicons dashicons-category"></span>
                    <div class="pfe-item-content">
                        <div class="pfe-file-name">${item.name}</div>
                        <div class="pfe-file-folder">${folderPath || 'Root'}</div>
                    </div>
                    <div class="pfe-file-modified">${item.modified}</div>
                </div>
            `;
        } else {
            html += `
                <div class="pfe-file-item">
                    <span class="pfe-file-icon dashicons dashicons-media-default"></span>
                    <div class="pfe-item-content">
                        <div class="pfe-file-name">${item.name}</div>
                        <div class="pfe-file-folder">${folderPath || 'Root'}</div>
                    </div>
                    <div class="pfe-file-info">
                        <span class="pfe-file-size">${item.size}</span>
                        <span class="pfe-file-modified">${item.modified}</span>
                          <div class="pfe-file-actions">
                        <a href="${item.url}" target="_blank" class="pfe-preview-button" title="Preview">
                            Preview
                        </a>
                        <a href="${item.url}" download class="pfe-download-button" title="Download">
                            Download
                        </a>
                    </div>
                    </div>

                </div>
            `;
        }
    });

    $('.pfe-search-results .pfe-results-list').html(html);
}


});