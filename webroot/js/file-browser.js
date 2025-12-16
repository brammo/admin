/**
 * File Browser
 * 
 * Handles file browsing operations in a modal such as navigating folders, selecting files, 
 * refreshing content, pagination, uploading files, and filtering.
 * 
 * Uses event delegation to ensure event handlers persist even after dynamic content reloads.
 * 
 * Dependencies: Bootstrap 5 (for modal handling)
 */

const FileBrowser = (function() {
    'use strict';

/*     FileBrowser.prototype = {};
    FileBrowser.prototype.modalId = null;
    FileBrowser.prototype.boundHandleClick = null;
    FileBrowser.prototype.boundHandleSubmit = null;
 */
    /**
     * Constructor
     * 
     * @param {string} modalId - The ID of the modal element (e.g. '#myModal')
     */
    function FileBrowser(modalId) {
        this.modalId = modalId;
    }

    FileBrowser.prototype.constructor = FileBrowser;

    /**
     * Initialize the FileBrowser by creating the modal and setting up event listeners
     */
    FileBrowser.prototype.init = function() {

        this.createModal();

        this.boundHandleClick = this.handleClick.bind(this);
        this.boundHandleSubmit = this.handleSubmit.bind(this);

        document.addEventListener('click', this.boundHandleClick);
        document.addEventListener('submit', this.boundHandleSubmit);
    }

    /**
     * Create modal method
     */
    FileBrowser.prototype.createModal = function() {

        this.modalElement = document.querySelector(this.modalId);
        if (!this.modalElement) {
            const modalDiv = document.createElement('div');
            modalDiv.id = this.modalId.replace('#', '');
            modalDiv.className = 'modal fade';
            modalDiv.tabIndex = -1;
            modalDiv.setAttribute('aria-hidden', 'true');
            modalDiv.innerHTML = `
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">File Browser</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Content will be loaded here -->
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modalDiv);
            this.modalElement = modalDiv;
        }
        if (this.modalElement && typeof bootstrap !== 'undefined') {
            this.modal = new bootstrap.Modal(this.modalElement, {
                backdrop: 'static',
                keyboard: false
            });
        }

        return this.modalElement;
    }

    /**
     * Show the Bootstrap modal
     */
    FileBrowser.prototype.showModal = function() {

        if (this.modal) {
            this.modal.show();
        }
    }

    /**
     * Main click event handler using event delegation
     * 
     * @param {Event} e - Click event
     */
    FileBrowser.prototype.handleClick = function(e) {

        this.handleSelect(e) ||
        this.handleFolder(e) ||
        this.handleRefresh(e) ||
        this.handlePagination(e) ||
        this.handleUpload(e) ||
        this.handleCreateFolder(e);
    }

    /**
     * Main submit event handler using event delegation
     * @param {Event} e - Submit event
     */
    FileBrowser.prototype.handleSubmit = function(e) {

        this.handleCreateFolderSubmit(e) ||
        this.handleFilterSubmit(e);
    }

    /**
     * Destroy event listeners (useful for cleanup)
     */
    FileBrowser.prototype.destroy = function() {
        
        document.removeEventListener('click', this.boundHandleClick);
        document.removeEventListener('submit', this.boundHandleSubmit);
    }

    /**
     * Handle file selection clicks
     * 
     * @param {Event} e - Click event
     */
    FileBrowser.prototype.handleSelect = function(e) {
        
        const selectFile = e.target.closest(this.modalId + ' .select');
        if (!selectFile) {
            return false;
        }

        e.preventDefault();
        const target = selectFile.dataset.target;
        const element = document.getElementById(target);
        if (element) {
            const url = selectFile.getAttribute('href');
            const type = selectFile.dataset.type;

            if (type == 'image') {
                const imageInput = element.querySelector('input[type="hidden"]');
                const previewImg = element.querySelector('.image-preview img');
                const filenameDiv = element.querySelector('.filename');
                const deleteBtn = element.querySelector('.delete');
                imageInput.value = url;
                previewImg.src = url;
                filenameDiv.textContent = url;
                filenameDiv.style.display = 'block';
                deleteBtn.style.display = 'inline-block';
            }

            this.hideModal();
        }
        
        return true;
    }

    /**
     * Handle folder clicks
     * 
     * @param {Event} e - Click event
     */
    FileBrowser.prototype.handleFolder = function(e) {

        const folder = e.target.closest(this.modalId + ' .folder');
        if (!folder) {
            return false;
        }

        e.preventDefault();
        this.loadContent(folder.getAttribute('href'));

        return true;
    }

    /**
     * Handle refresh clicks
     * @param {Event} e - Click event
     */
    FileBrowser.prototype.handleRefresh = function(e) {
        
        const element = e.target.closest(this.modalId + ' .refresh');
        if (!element) return false;

        e.preventDefault();
        this.loadContent(element.getAttribute('href'));
        return true;
    }

    /**
     * Handle pagination clicks
     * @param {Event} e - Click event
     */
    FileBrowser.prototype.handlePagination = function(e) {

        const element = e.target.closest(this.modalId + ' .pagination a');
        if (!element) return false;

        e.preventDefault();
        this.loadContent(element.getAttribute('href'));
        return true;
    }

    /**
     * Handle file upload
     * @param {Event} e - Click event
     */
    FileBrowser.prototype.handleUpload = function(e) {

        const btnUpload = e.target.closest(this.modalId + ' .upload');
        if (!btnUpload) return false;

        e.preventDefault();

        const form = document.querySelector(this.modalId + ' .upload-form');
        if (!form) return true;

        const fileInput = form.querySelector('input[type="file"]');
        if (!fileInput) return true;
        
        const self = this;
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
                    const refreshBtn = document.querySelector(self.modalId + ' .refresh');
                    if (refreshBtn) {
                        self.loadContent(refreshBtn.getAttribute('href'));
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
    FileBrowser.prototype.handleCreateFolder = function(e) {

        const btn = e.target.closest(this.modalId + ' .create-folder');
        if (!btn) return false;

        e.preventDefault();
        
        const form = btn.closest(this.modalId + ' .create-folder-form');
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
     * Handle create folder form submission
     * @param {Event} e - Submit event
     */
    FileBrowser.prototype.handleCreateFolderSubmit = function(e) {
        
        const form = e.target.closest(this.modalId + ' .create-folder-form');
        if (!form) return false;

        e.preventDefault();
        
        const btn = form.querySelector('.create-folder');
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
        
        const self = this;
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
                const refreshBtn = document.querySelector(self.modalId + ' .refresh');
                if (refreshBtn) {
                    self.loadContent(refreshBtn.getAttribute('href'));
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
    FileBrowser.prototype.handleFilterSubmit = function(e) {

        const filterForm = e.target.closest(this.modalId + ' .filter-form');
        if (!filterForm) return false;

        e.preventDefault();
        const filterInput = document.querySelector(this.modalId + ' .filter-input');
        const filter = filterInput ? filterInput.value : '';
        const url = filterForm.getAttribute('action') + '&filter=' + encodeURIComponent(filter);
        this.loadContent(url);
        return true;
    }

    /**
     * Load content via fetch into the modal container
     * @param {string} url - The URL to fetch content from
     * @returns {Promise}
     */
    FileBrowser.prototype.loadContent = function(url) {

        const container = document.querySelector(this.modalId + ' .modal-body');
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
     * Hide a Bootstrap modal
     */
    FileBrowser.prototype.hideModal = function() {

        // modalId is stored with # prefix for selectors, remove it for getElementById
        const modalEl = document.querySelector(this.modalId);
        if (modalEl && typeof bootstrap !== 'undefined') {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        }
    }

    return FileBrowser;
})();
