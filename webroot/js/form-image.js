/**
 * FormImage Class
 * 
 * Handles image selection, upload, and deletion within a form.
 */
const FormImage = (function() {

    /**
     * Constructor
     * 
     * @param {string} elementId - The ID of the form image element
     * @param {string} browseUrl - The URL to open the file manager
     * @param {string} uploadUrl - The URL to upload images
     * @param {string} modalTitle - The title for the file browser modal
     */
    function FormImage(elementId, browseUrl, uploadUrl, modalTitle = 'File Browser') {

        this.element = document.getElementById(elementId);
        this.browseUrl = browseUrl;
        this.uploadUrl = uploadUrl;
        this.modalTitle = modalTitle;
        this.csrfToken = null;

        // Get CSRF token from parent form if available
        const form = this.element.closest('form');
        if (form) {
            const tokenInput = form.querySelector('input[name="_csrfToken"]');
            if (tokenInput) {
                this.csrfToken = tokenInput.value;
            }
        }
        
        this.fileBrowser = new FileBrowser('#file-browser-modal', this.modalTitle);

        this.init();
    }

    /**
     * Initialize event listeners
     */
    FormImage.prototype.init = function() {

        const selectBtn = this.element.querySelector('.select');
        const deleteBtn = this.element.querySelector('.delete');
        const uploadInput = this.element.querySelector('.upload input[type="file"]');

        if (!selectBtn || !deleteBtn || !uploadInput) {
            console.error('FormImage: Missing required elements.');
            return;
        }

        selectBtn.addEventListener('click', this.openFileBrowser.bind(this));
        deleteBtn.addEventListener('click', this.deleteImage.bind(this));
        uploadInput.addEventListener('change', this.uploadImage.bind(this));
    };

    /**
     * Open file manager modal
     */
    FormImage.prototype.openFileBrowser = function(e) {
        e.preventDefault();

        const folder = this.element.dataset.folder || '';
        const target = this.element.id;
        const url = this.browseUrl + '?folder=' + encodeURIComponent(folder) + 
            '&target=' + encodeURIComponent(target);

        this.fileBrowser.loadContent(url);
        this.fileBrowser.showModal();
    };

    /**
     * Delete selected image
     */
    FormImage.prototype.deleteImage = function(e) {
        e.preventDefault();
        const imageInput = this.element.querySelector('input[type="hidden"]');
        const previewImg = this.element.querySelector('.image-preview img');
        const filenameDiv = this.element.querySelector('.filename');
        const deleteBtn = this.element.querySelector('.delete');
        
        imageInput.value = '';
        previewImg.src = '';
        filenameDiv.textContent = '';
        filenameDiv.style.display = 'none';
        deleteBtn.style.display = 'none';
    };

    /**
     * Upload an image file to the server
     */
    FormImage.prototype.uploadImage = function(e) {

        const file = e.target.files[0];
        if (!file) return;

        const folder = this.element.dataset.folder || '';

        const formData = new FormData();
        formData.append('file', file);
        fetch(this.uploadUrl + '?folder=' + encodeURIComponent(folder), {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': this.csrfToken || ''
            }
        })
        .then(response => response.json())
        .then(json => {
            if (json.error) {
                alert(json.error);
            } else {

                const files = json.files || [];
                if (files.length === 0) {
                    alert('No file was uploaded.');
                    return;
                }
                const file = '/' + folder + '/' + files[0];

                const imageInput = this.element.querySelector('input[type="hidden"]');
                const previewImg = this.element.querySelector('.image-preview img');
                const filenameDiv = this.element.querySelector('.filename');
                const deleteBtn = this.element.querySelector('.delete');

                imageInput.value = file;
                previewImg.src = file;
                filenameDiv.textContent = file;
                filenameDiv.style.display = 'block';
                deleteBtn.style.display = 'inline-block';
            }
        })
        .catch(() => {
            alert('An error occurred while uploading the image.');
        });
    };

    return FormImage;
})();
