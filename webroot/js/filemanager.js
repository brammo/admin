/**
 * File Manager
 * 
 * Handles file management operations such as loading content,
 * selecting files, navigating folders, refreshing content,
 * pagination, uploading files, and filtering.
 * 
 * Uses event delegation to ensure event handlers persist
 * even after dynamic content reloads.
 * 
 * Dependencies: Bootstrap 5 (for modal handling)
 */

const FileManager = (function() {
    'use strict';

    const SELECTORS = {
        container: '#modal-filemanager-content',
        modal: '#modal-filemanager',
        folder: '#modal-filemanager .folder',
        selectFile: '#modal-filemanager .select-file',
        refreshBtn: '#button-refresh',
        uploadBtn: '#button-upload',
        uploadForm: '#form-upload',
        createFolderBtn: '#btn-create-folder',
        createFolderForm: '#form-create-folder',
        pagination: '#modal-filemanager-content .pagination a',
        filterForm: '#form-filter',
        filterInput: 'input[name="filter"]'
    };

    /**
     * Load content via fetch into the modal container
     * @param {string} url - The URL to fetch content from
     * @returns {Promise}
     */
    function loadContent(url) {

        const container = document.querySelector(SELECTORS.container);
        if (!container) {
            return Promise.reject(new Error('Container not found'));
        }

        container.innerHTML = '';
        
        return fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            container.innerHTML = html;
            return html;
        })
        .catch(error => {
            console.error('Error loading content:', error);
            throw error;
        });
    }

    /**
     * Hide a Bootstrap modal by ID
     * @param {string} modalId - The modal element ID
     */
    function hideModal(modalId) {

        const modalEl = document.getElementById(modalId);
        if (modalEl && typeof bootstrap !== 'undefined') {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        }
    }

    /**
     * Handle file selection clicks
     * @param {Event} e - Click event
     */
    function handleFileSelect(e) {

        const selectFile = e.target.closest(SELECTORS.selectFile);
        if (!selectFile) return false;

        e.preventDefault();
        const url = selectFile.getAttribute('href');
        const target = selectFile.dataset.target;
        const element = document.getElementById(target);

        if (element) {
            element.value = url;
            const parent = element.parentElement;

            const parentImg = parent.querySelector('img');
            if (parentImg) {
                parentImg.setAttribute('src', url);
            }

            const filename = parent.querySelector('.filename');
            if (filename) {
                filename.textContent = url;
            }

            const deleteBtn = parent.querySelector('.delete');
            if (deleteBtn) {
                deleteBtn.style.display = '';
            }
        }

        hideModal('modal-filemanager');
        return true;
    }

    /**
     * Handle folder navigation clicks
     * @param {Event} e - Click event
     */
    function handleFolderClick(e) {

        const folder = e.target.closest(SELECTORS.folder);
        if (!folder) return false;

        e.preventDefault();
        loadContent(folder.getAttribute('href'));
        return true;
    }

    /**
     * Handle refresh clicks
     * @param {Event} e - Click event
     */
    function handleRefresh(e) {
        
        const element = e.target.closest(SELECTORS.refreshBtn);
        if (!element) return false;

        e.preventDefault();
        loadContent(element.getAttribute('href'));
        return true;
    }

    /**
     * Handle pagination clicks
     * @param {Event} e - Click event
     */
    function handlePagination(e) {

        const element = e.target.closest(SELECTORS.pagination);
        if (!element) return false;

        e.preventDefault();
        loadContent(element.getAttribute('href'));
        return true;
    }

    /**
     * Handle file upload
     * @param {Event} e - Click event
     */
    function handleUpload(e) {

        const btnUpload = e.target.closest(SELECTORS.uploadBtn);
        if (!btnUpload) return false;

        e.preventDefault();

        const form = document.querySelector(SELECTORS.uploadForm);
        if (!form) return true;

        const fileInput = form.querySelector('input[type="file"]');
        if (!fileInput) return true;
        
        fileInput.addEventListener('change', function handleFileChange() {
            const formData = new FormData(form);
            
            // Get CSRF token
            const csrfToken = form.querySelector('input[name="_csrfToken"]');
            
            btnUpload.disabled = true;

            fetch(form.getAttribute('action'), {
                method: 'POST',
                body: formData,
                headers: csrfToken ? { 'X-CSRF-Token': csrfToken.value } : {}
            })
            .then(response => response.json())
            .then(json => {
                btnUpload.disabled = false;
                if (json.error) {
                    alert(json.error);
                } else {
                    // Refresh the file list
                    const refreshBtn = document.querySelector(SELECTORS.refreshBtn);
                    if (refreshBtn) {
                        loadContent(refreshBtn.getAttribute('href'));
                    }
                }
            })
            .catch(error => {
                btnUpload.disabled = false;
                alert('Error: ' + error.message);
            });
        }, { once: true });

        fileInput.click();
        return true;
    }

    /**
     * Handle add folder button click
     * @param {Event} e - Click event
     */
    function handleCreateFolder(e) {
        const btn = e.target.closest(SELECTORS.createFolderBtn);
        if (!btn) return false;

        e.preventDefault();
        
        const form = btn.closest(SELECTORS.createFolderForm);
        if (!form) return true;
        
        const inputGroup = form.querySelector('.input-group');
        if (!inputGroup) return true;
        
        // Show input group, hide button
        btn.style.display = 'none';
        inputGroup.style.display = '';
        
        // Focus on the input
        const input = inputGroup.querySelector('input[name="folder"]');
        if (input) {
            input.focus();
        }
        
        return true;
    }

    /**
     * Handle add folder form submission
     * @param {Event} e - Submit event
     */
    function handleCreateFolderSubmit(e) {
        const form = e.target.closest(SELECTORS.createFolderForm);
        if (!form) return false;

        e.preventDefault();
        
        const btn = form.querySelector(SELECTORS.createFolderBtn);
        const inputGroup = form.querySelector('.input-group');
        const input = form.querySelector('input[name="folder"]');
        const folderName = input ? input.value.trim() : '';
        
        if (!folderName) {
            if (input) input.focus();
            return true;
        }
        
        const formData = new FormData(form);
        
        // Get CSRF token
        const csrfToken = form.querySelector('input[name="_csrfToken"]');
        
        fetch(form.getAttribute('action'), {
            method: 'POST',
            body: formData,
            headers: csrfToken ? { 'X-CSRF-Token': csrfToken.value } : {}
        })
        .then(response => response.json())
        .then(json => {
            if (json.error) {
                alert(json.error);
            } else {
                // Reset and hide input group, show button
                if (input) input.value = '';
                if (inputGroup) inputGroup.style.display = 'none';
                if (btn) btn.style.display = '';
                
                // Refresh the file list
                const refreshBtn = document.querySelector(SELECTORS.refreshBtn);
                if (refreshBtn) {
                    loadContent(refreshBtn.getAttribute('href'));
                }
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
        
        return true;
    }

    /**
     * Handle filter form submission
     * @param {Event} e - Submit event
     */
    function handleFilterSubmit(e) {

        const filterForm = e.target.closest(SELECTORS.filterForm);
        if (!filterForm) return false;

        e.preventDefault();
        const filterInput = document.querySelector(SELECTORS.filterInput);
        const filter = filterInput ? filterInput.value : '';
        const url = filterForm.getAttribute('action') + '&filter=' + encodeURIComponent(filter);
        loadContent(url);
        return true;
    }

    /**
     * Main click event handler using event delegation
     * @param {Event} e - Click event
     */
    function handleClick(e) {
        handleFileSelect(e) ||
        handleFolderClick(e) ||
        handleRefresh(e) ||
        handlePagination(e) ||
        handleUpload(e) ||
        handleCreateFolder(e);
    }

    /**
     * Main submit event handler using event delegation
     * @param {Event} e - Submit event
     */
    function handleSubmit(e) {
        handleAddFolderSubmit(e) ||
        handleFilterSubmit(e);
    }

    /**
     * Initialize the file manager
     */
    function init() {

        // Use event delegation on document for persistent event handling
        // Events will work even after content is dynamically reloaded
        document.addEventListener('click', handleClick);
        document.addEventListener('submit', handleSubmit);
    }

    /**
     * Destroy event listeners (useful for cleanup)
     */
    function destroy() {
        document.removeEventListener('click', handleClick);
        document.removeEventListener('submit', handleSubmit);
    }

    // Public API
    return {
        init,
        destroy,
        loadContent,
        hideModal
    };
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', FileManager.init);
