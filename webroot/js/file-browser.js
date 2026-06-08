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

    /**
     * Constructor
     * 
     * @param {string} modalId - The ID of the modal element (e.g. '#myModal')
     * @param {string} title - The title text for the modal
     */
    function FileBrowser(modalId, title = 'File Browser') {

        this.modalId = modalId;
        this.title = title;
        this.embeddedSelector = null;
        this.loadTarget = 'modal';

        this.init();
        this.createModal();
    }

    FileBrowser.prototype.constructor = FileBrowser;

    /**
     * Initialize the FileBrowser by creating the modal and setting up event listeners
     */
    FileBrowser.prototype.init = function() {

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
                            <h5 class="modal-title"></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Content will be loaded here -->
                        </div>
                    </div>
                </div>
            `;
            // Set title via textContent to prevent XSS
            modalDiv.querySelector('.modal-title').textContent = this.title;
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
     * @param {string|null} selector
     */
    FileBrowser.prototype.setEmbeddedSelector = function(selector) {
        this.embeddedSelector = selector;
    }

    /**
     * @returns {Array<string>}
     */
    FileBrowser.prototype.getRoots = function() {
        const roots = [this.modalId];
        if (this.embeddedSelector) {
            roots.push(this.embeddedSelector);
        }
        return roots;
    }

    /**
     * @param {Element} target
     * @param {string} subselector
     * @returns {{element: Element, root: string}|null}
     */
    FileBrowser.prototype.closestInRoots = function(target, subselector) {
        const roots = this.getRoots();
        for (let i = 0; i < roots.length; i++) {
            const match = target.closest(roots[i] + ' ' + subselector);
            if (match) {
                return { element: match, root: roots[i] };
            }
        }
        return null;
    }

    /**
     * @param {string} root
     * @returns {Element|null}
     */
    FileBrowser.prototype.getContainerForRoot = function(root) {
        if (root === this.embeddedSelector) {
            return document.querySelector(this.embeddedSelector);
        }
        return document.querySelector(this.modalId + ' .modal-body');
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
        const match = this.closestInRoots(e.target, '.select');
        if (!match) {
            return false;
        }

        const selectFile = match.element;
        e.preventDefault();
        const target = selectFile.dataset.target;
        const element = document.getElementById(target);
        if (element) {
            const url = selectFile.getAttribute('href');

            if (element.dataset.editorId &&
                window.BrammoEditor &&
                window.BrammoEditor.instances[element.dataset.editorId]) {
                const editor = window.BrammoEditor.instances[element.dataset.editorId];

                if (element.dataset.editorLinkTarget && editor.linkDialogOpen && editor.linkBrowseEmbedded) {
                    editor.setLinkDialogUrl(url);
                    editor.showLinkFormView();
                    return true;
                }

                if (editor.imageDialogOpen && editor.imageBrowseEmbedded) {
                    editor.setImageDialogUrl(url);
                    editor.showImageFormView();
                    return true;
                }
                if (editor.imageDialogOpen) {
                    editor.setImageDialogUrl(url);
                }
                this.hideModal();
                return true;
            }

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
        const match = this.closestInRoots(e.target, '.folder');
        if (!match) {
            return false;
        }

        e.preventDefault();
        this.loadContent(match.element.getAttribute('href'), match.root);

        return true;
    }

    /**
     * Handle refresh clicks
     * @param {Event} e - Click event
     */
    FileBrowser.prototype.handleRefresh = function(e) {
        const match = this.closestInRoots(e.target, '.refresh');
        if (!match) {
            return false;
        }

        e.preventDefault();
        this.loadContent(match.element.getAttribute('href'), match.root);
        return true;
    }

    /**
     * Handle pagination clicks
     * @param {Event} e - Click event
     */
    FileBrowser.prototype.handlePagination = function(e) {
        const match = this.closestInRoots(e.target, '.pagination a');
        if (!match) {
            return false;
        }

        e.preventDefault();
        this.loadContent(match.element.getAttribute('href'), match.root);
        return true;
    }

    /**
     * Handle file upload
     * @param {Event} e - Click event
     */
    FileBrowser.prototype.handleUpload = function(e) {
        const match = this.closestInRoots(e.target, '.upload');
        if (!match) {
            return false;
        }

        const btnUpload = match.element;
        e.preventDefault();

        const form = match.root ? document.querySelector(match.root + ' .upload-form') : null;
        if (!form) {
            return true;
        }

        const fileInput = form.querySelector('input[type="file"]');
        if (!fileInput) {
            return true;
        }

        const self = this;
        const loadRoot = match.root;
        fileInput.addEventListener('change', function handleFileChange() {
            const formData = new FormData(form);

            const csrfToken = form.querySelector('input[name="_csrfToken"]');

            btnUpload.disabled = true;

            fetch(form.getAttribute('action'), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken ? csrfToken.value : ''
                }
            })
            .then(response => response.json())
            .then(json => {
                btnUpload.disabled = false;
                if (json.error) {
                    alert(json.error);
                } else {
                    const refreshBtn = document.querySelector(loadRoot + ' .refresh');
                    if (refreshBtn) {
                        self.loadContent(refreshBtn.getAttribute('href'), loadRoot);
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
        const match = this.closestInRoots(e.target, '.create-folder');
        if (!match) {
            return false;
        }

        const btn = match.element;
        e.preventDefault();

        const form = btn.closest('.create-folder-form');
        const rootEl = document.querySelector(match.root);
        if (!form || !rootEl || !rootEl.contains(form)) {
            return true;
        }

        const inputGroup = form.querySelector('.input-group');
        if (!inputGroup) {
            return true;
        }

        btn.style.display = 'none';
        inputGroup.style.display = '';

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
        const roots = this.getRoots();
        let form = null;
        let loadRoot = this.modalId;

        for (let i = 0; i < roots.length; i++) {
            form = e.target.closest(roots[i] + ' .create-folder-form');
            if (form) {
                loadRoot = roots[i];
                break;
            }
        }

        if (!form) {
            return false;
        }

        e.preventDefault();

        const btn = form.querySelector('.create-folder');
        const inputGroup = form.querySelector('.input-group');
        const input = form.querySelector('input[name="folder"]');
        const folderName = input ? input.value.trim() : '';

        if (!folderName) {
            if (input) {
                input.focus();
            }
            return true;
        }

        const formData = new FormData(form);
        const csrfToken = form.querySelector('input[name="_csrfToken"]');
        const self = this;

        fetch(form.getAttribute('action'), {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken ? csrfToken.value : ''
            }
        })
        .then(response => response.json())
        .then(json => {
            if (json.error) {
                alert(json.error);
            } else {
                if (input) {
                    input.value = '';
                }
                if (inputGroup) {
                    inputGroup.style.display = 'none';
                }
                if (btn) {
                    btn.style.display = '';
                }

                const refreshBtn = document.querySelector(loadRoot + ' .refresh');
                if (refreshBtn) {
                    self.loadContent(refreshBtn.getAttribute('href'), loadRoot);
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
        const roots = this.getRoots();
        let filterForm = null;
        let loadRoot = this.modalId;

        for (let i = 0; i < roots.length; i++) {
            filterForm = e.target.closest(roots[i] + ' .filter-form');
            if (filterForm) {
                loadRoot = roots[i];
                break;
            }
        }

        if (!filterForm) {
            return false;
        }

        e.preventDefault();
        const filterInput = document.querySelector(loadRoot + ' .filter-input');
        const filter = filterInput ? filterInput.value : '';
        const url = filterForm.getAttribute('action') + '&filter=' + encodeURIComponent(filter);
        this.loadContent(url, loadRoot);
        return true;
    }

    /**
     * Load content via fetch into the modal or embedded container
     * @param {string} url
     * @param {string} [root]
     * @returns {Promise}
     */
    FileBrowser.prototype.loadContent = function(url, root) {
        if (root === this.embeddedSelector) {
            this.loadTarget = 'embedded';
        } else if (root === this.modalId) {
            this.loadTarget = 'modal';
        }

        const container = this.loadTarget === 'embedded' && this.embeddedSelector
            ? document.querySelector(this.embeddedSelector)
            : document.querySelector(this.modalId + ' .modal-body');

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
