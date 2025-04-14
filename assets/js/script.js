document.addEventListener('DOMContentLoaded', () => {
    const fileList = document.querySelector('.pfe-file-list');
    const searchResults = document.querySelector('.pfe-search-results');
    const searchResultsList = document.querySelector('.pfe-search-results .pfe-results-list');
    const searchInput = document.getElementById('pfe-search-input');
    const searchButton = document.getElementById('pfe-search-button');
    const breadcrumb = document.querySelector('.pfe-breadcrumb');
    const layoutButtons = document.querySelectorAll('.pfe-layout-btn');

    let currentPath = '';
    let searchActive = false;
    let currentLayout = localStorage.getItem('pfe_layout_preference') || 'list';
    let searchTimeout;

    // Initialize layout
    layoutButtons.forEach(button => {
        button.classList.remove('active');
        if (button.dataset.layout === currentLayout) {
            button.classList.add('active');
        }
    });
    fileList.classList.add(`${currentLayout}-view`);

    // Layout change handler
    layoutButtons.forEach(button => {
        button.addEventListener('click', function () {
            const layout = this.dataset.layout;
            if (layout !== currentLayout) {
                layoutButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                fileList.classList.remove('list-view', 'grid-view');
                fileList.classList.add(`${layout}-view`);
                localStorage.setItem('pfe_layout_preference', layout);
                currentLayout = layout;
            }
        });
    });

    // Load initial directory
    loadDirectory('');

    // Directory navigation handlers (event delegation)
    document.addEventListener('click', (event) => {
        if (event.target.closest('.pfe-go-up-item') || event.target.closest('.pfe-folder-item') || event.target.closest('.pfe-breadcrumb-item')) {
            const target = event.target.closest('[data-path]');
            if (target) {
                const path = target.dataset.path;
                currentPath = path;
                loadDirectory(path);
                if (searchActive) {
                    searchResults.style.display = 'none';
                    fileList.style.display = '';
                    searchActive = false;
                }
            }
        }
    });

    // Search input handler (debounced)
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        const searchTerm = searchInput.value.trim();
        searchTimeout = setTimeout(() => {
            if (searchTerm.length > 0) {
                searchFiles(searchTerm);
            } else if (searchActive) {
                searchResults.style.display = 'none';
                fileList.style.display = '';
                searchActive = false;
                updateBreadcrumb(currentPath);
            }
        }, 250);
    });

    // Search button click handler
    searchButton.addEventListener('click', () => {
        clearTimeout(searchTimeout);
        const searchTerm = searchInput.value.trim();
        if (searchTerm.length > 0) {
            searchFiles(searchTerm);
        }
    });

    // Back to browse handler
    document.addEventListener('click', (event) => {
        if (event.target.closest('.pfe-back-to-browse')) {
            searchResults.style.display = 'none';
            fileList.style.display = '';
            searchActive = false;
            updateBreadcrumb(currentPath);
        }
    });

    function loadDirectory(path) {
        fetch(pfe_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                action: 'pfe_browse_folder',
                path: path,
                nonce: pfe_ajax.nonce
            })
        })
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                renderDirectory(response.data);
                updateBreadcrumb(path);
            } else {
                console.error('Server Error:', response.data);
                fileList.innerHTML = `<div class="pfe-error">${response.data}</div>`;
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            fileList.innerHTML = `<div class="pfe-error">Error loading directory.</div>`;
        });
    }

    function renderDirectory(items) {
        if (items.length === 0 && currentPath === '') {
            fileList.innerHTML = '<div class="pfe-empty">This folder is empty</div>';
            return;
        }

        let html = '';
        if (currentPath !== '') {
            const parentPath = currentPath.split('/').slice(0, -1).join('/');
            html += `
                <div class="pfe-go-up-item" data-path="${parentPath}">
                    <span class="pfe-go-up-icon dashicons dashicons-arrow-up-alt"></span>
                    <div class="pfe-item-content">
                        <span class="pfe-go-up-text">Go Up</span>
                    </div>
                </div>
            `;
        }

        items.sort((a, b) => {
            if (a.type === 'folder' && b.type !== 'folder') return -1;
            if (a.type !== 'folder' && b.type === 'folder') return 1;
            return a.name.localeCompare(b.name);
        });

        items.forEach(item => {
            const iconClass = item.type === 'folder' ? 'dashicons-category' : 'dashicons-media-default';
            const itemClass = item.type === 'folder' ? 'pfe-folder-item' : 'pfe-file-item';
            const dataPath = item.type === 'folder' ? `data-path="${item.path}"` : '';
            const actions = item.type === 'file' ? `
                <div class="pfe-file-actions">
                    <a href="${item.url}" target="_blank" class="pfe-preview-button" title="Preview">Preview</a>
                    <a href="${item.url}" download class="pfe-download-button" title="Download">Download</a>
                </div>
            ` : '';
            const info = item.type === 'file' ? `
                <span class="pfe-file-size">${item.size}</span>
            ` : '';

            html += `
                <div class="${itemClass}" ${dataPath}>
                    <span class="pfe-item-icon dashicons ${iconClass}"></span>
                    <div class="pfe-item-content">
                        <span class="pfe-file-name">${item.name}</span>
                    </div>
                    <div class="pfe-file-info">
                        ${info}
                        <span class="pfe-file-modified">${item.modified}</span>
                        ${actions}
                    </div>
                </div>
            `;
        });

        fileList.innerHTML = html;
    }

    function updateBreadcrumb(path) {
        if (searchActive) return;
        const parts = path.split('/').filter(Boolean);
        let breadcrumbHtml = '<span class="pfe-breadcrumb-item" data-path="">Root</span>';
        let currentPath = '';
        parts.forEach(part => {
            currentPath += (currentPath ? '/' : '') + part;
            breadcrumbHtml += `<span class="pfe-breadcrumb-item" data-path="${currentPath}">${part}</span>`;
        });
        breadcrumb.innerHTML = breadcrumbHtml;
    }

    function searchFiles(term) {
        fileList.style.display = 'none';
        searchResults.style.display = '';
        searchResultsList.innerHTML = '<div class="pfe-loading">Searching...</div>';
        searchActive = true;

        fetch(pfe_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                action: 'pfe_search_files',
                search: term,
                path: currentPath,
                nonce: pfe_ajax.nonce,
                include_folders: true
            })
        })
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                renderSearchResults(response.data, term);
            } else {
                searchResultsList.innerHTML = `<div class="pfe-error">${response.data}</div>`;
            }
        })
        .catch(error => {
            const errorMsg = error.responseJSON?.data || 'Search failed';
            searchResultsList.innerHTML = `<div class="pfe-error">${errorMsg}</div>`;
        });
    }

    function renderSearchResults(items, term) {
        if (items.length === 0) {
            searchResultsList.innerHTML = `<div class="pfe-empty">No items found for "${term}"</div>`;
            return;
        }

        let html = `<div class="pfe-search-summary">Found ${items.length} items matching "${term}"</div>`;
        items.forEach(item => {
            const folderPath = item.path.split('/').slice(0, -1).join(' / ') || 'Root';
            const iconClass = item.type === 'folder' ? 'dashicons-category' : 'dashicons-media-default';
            const itemClass = item.type === 'folder' ? 'pfe-folder-item' : 'pfe-file-item';
            const dataPath = `data-path="${item.path}"`;
            const actions = item.type === 'file' ? `
                <div class="pfe-file-actions">
                    <a href="${item.url}" target="_blank" class="pfe-preview-button" title="Preview">Preview</a>
                    <a href="${item.url}" download class="pfe-download-button" title="Download">Download</a>
                </div>
            ` : '';
            const sizeInfo = item.type === 'file' ? `<span class="pfe-file-size">${item.size}</span>` : '';

            html += `
                <div class="${itemClass} pfe-search-result-item" ${dataPath}>
                    <span class="pfe-item-icon dashicons ${iconClass}"></span>
                    <div class="pfe-item-content">
                        <div class="pfe-file-name">${item.name}</div>
                        <div class="pfe-file-folder">${folderPath}</div>
                    </div>
                    <div class="pfe-file-info">
                        ${sizeInfo}
                        <span class="pfe-file-modified">${item.modified}</span>
                        ${actions}
                    </div>
                </div>
            `;
        });
        searchResultsList.innerHTML = html;
    }

    // Nonce refresh mechanism (example - call if a request fails with a nonce error)
    // function pfe_refresh_nonce() {
    //     fetch(pfe_ajax.ajax_url, {
    //         method: 'POST',
    //         headers: {
    //             'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
    //         },
    //         body: new URLSearchParams({
    //             action: 'pfe_refresh_nonce'
    //         })
    //     })
    //     .then(response => response.json())
    //     .then(response => {
    //         if (response.success) {
    //             pfe_ajax.nonce = response.data.nonce;
    //             // Optionally, retry the last failed request
    //         }
    //     })
    //     .catch(error => console.error('Error refreshing nonce:', error));
    // }
});