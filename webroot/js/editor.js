/**
 * HTML Editor
 *
 * Lightweight contenteditable WYSIWYG editor for admin forms.
 * Syncs content to the underlying textarea on input, blur, and form submit.
 *
 * Dependencies: Bootstrap 5, FileBrowser (for image picker)
 */
const HtmlEditor = (function() {
    'use strict';

    const ALIGN_ACTIONS = {
        alignLeft: 'left',
        alignCenter: 'center',
        alignRight: 'right',
        alignJustify: 'justify',
    };

    const BLOCK_TAGS = ['H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'P', 'DIV', 'BLOCKQUOTE', 'PRE', 'LI'];
    const FORMAT_BLOCK_TAGS = ['H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'P', 'DIV', 'BLOCKQUOTE', 'PRE'];

    /**
     * @param {HTMLTextAreaElement} textarea
     * @param {Object} options
     */
    function HtmlEditor(textarea, options) {
        this.textarea = textarea;
        this.options = options || {};
        this.id = textarea.id || ('editor-' + Math.random().toString(36).slice(2, 9));

        if (!textarea.id) {
            textarea.id = this.id;
        }

        this.sourceMode = false;
        this.fileBrowser = options.fileBrowser || null;
        this.imageDialogOpen = false;
        this.imageBrowseEmbedded = false;
        this.editingImage = null;
        this.linkDialogOpen = false;
        this.linkBrowseEmbedded = false;
        this.editingLink = null;
        this.savedSelection = null;
        this.selectedImage = null;

        window.BrammoEditor.instances[this.id] = this;
        this.init();
    }

    HtmlEditor.prototype.init = function() {
        const wrapper = document.createElement('div');
        wrapper.className = 'html-editor';
        wrapper.style.setProperty('--html-editor-height', (this.options.height || 500) + 'px');
        this.textarea.parentNode.insertBefore(wrapper, this.textarea);
        wrapper.appendChild(this.textarea);

        this.imageTarget = document.createElement('div');
        this.imageTarget.id = 'editor-img-target-' + this.id;
        this.imageTarget.dataset.editorId = this.id;
        this.imageTarget.hidden = true;
        wrapper.appendChild(this.imageTarget);

        this.linkTarget = document.createElement('div');
        this.linkTarget.id = 'editor-link-target-' + this.id;
        this.linkTarget.dataset.editorId = this.id;
        this.linkTarget.dataset.editorLinkTarget = '1';
        this.linkTarget.hidden = true;
        wrapper.appendChild(this.linkTarget);

        this.toolbar = this.buildToolbar();
        wrapper.insertBefore(this.toolbar, this.textarea);

        this.body = document.createElement('div');
        this.body.className = 'html-editor-body';
        this.body.contentEditable = 'true';
        this.body.innerHTML = this.textarea.value;
        this.body.setAttribute('role', 'textbox');
        this.body.setAttribute('aria-multiline', 'true');
        wrapper.insertBefore(this.body, this.textarea);

        this.textarea.classList.add('html-editor-source');
        this.textarea.style.display = 'none';
        this.textarea.setAttribute('aria-hidden', 'true');

        this.body.addEventListener('input', function() {
            this.sync();
            this.refreshToolbarState();
        }.bind(this));
        this.body.addEventListener('blur', this.sync.bind(this));
        this.body.addEventListener('keyup', this.refreshToolbarState.bind(this));
        this.body.addEventListener('mouseup', function() {
            this.trackSelectedImage();
            this.refreshToolbarState();
        }.bind(this));
        this.body.addEventListener('click', function(e) {
            const img = e.target.closest('img');
            if (img && this.body.contains(img)) {
                this.selectedImage = img;
            }
        }.bind(this));
        this.body.addEventListener('dblclick', function(e) {
            if (this.sourceMode) {
                return;
            }
            const link = e.target.closest('a');
            if (link && this.body.contains(link)) {
                e.preventDefault();
                this.openLinkDialog(link);
                return;
            }
            const img = e.target.closest('img');
            if (img && this.body.contains(img)) {
                e.preventDefault();
                this.openImageDialog(img);
            }
        }.bind(this));

        this.onSelectionChange = this.onSelectionChange.bind(this);
        document.addEventListener('selectionchange', this.onSelectionChange);

        const form = this.textarea.closest('form');
        if (form) {
            form.addEventListener('submit', this.sync.bind(this));
        }

        this.refreshToolbarState();
    };

    /**
     * @returns {HTMLElement}
     */
    HtmlEditor.prototype.buildToolbar = function() {
        const labels = this.options.labels || {};
        const toolbar = document.createElement('div');
        toolbar.className = 'html-editor-toolbar btn-toolbar flex-wrap gap-1';
        toolbar.setAttribute('role', 'toolbar');

        toolbar.appendChild(this.buildButtonGroup([
            { cmd: 'undo', icon: 'bi-arrow-counterclockwise', title: labels.undo },
            { cmd: 'redo', icon: 'bi-arrow-clockwise', title: labels.redo },
        ]));
        toolbar.appendChild(this.buildBlockSelect(labels));
        toolbar.appendChild(this.buildButtonGroup([
            { cmd: 'bold', icon: 'bi-type-bold', title: labels.bold },
            { cmd: 'italic', icon: 'bi-type-italic', title: labels.italic },
            { cmd: 'underline', icon: 'bi-type-underline', title: labels.underline },
            { cmd: 'strikeThrough', icon: 'bi-type-strikethrough', title: labels.strikethrough },
            { cmd: 'subscript', icon: 'bi-subscript', title: labels.subscript },
            { cmd: 'superscript', icon: 'bi-superscript', title: labels.superscript },
            { action: 'wrapCode', icon: 'bi-code', title: labels.code },
        ]));
        toolbar.appendChild(this.buildButtonGroup([
            { action: 'alignLeft', icon: 'bi-text-left', title: labels.alignLeft },
            { action: 'alignCenter', icon: 'bi-text-center', title: labels.alignCenter },
            { action: 'alignRight', icon: 'bi-text-right', title: labels.alignRight },
            { action: 'alignJustify', icon: 'bi-justify', title: labels.alignJustify },
        ]));
        toolbar.appendChild(this.buildButtonGroup([
            { cmd: 'insertUnorderedList', icon: 'bi-list-ul', title: labels.unorderedList },
            { cmd: 'insertOrderedList', icon: 'bi-list-ol', title: labels.orderedList },
        ]));
        toolbar.appendChild(this.buildButtonGroup([
            { action: 'insertLink', icon: 'bi-link-45deg', title: labels.link },
            { action: 'insertImage', icon: 'bi-image', title: labels.imageBrowse },
        ]));
        toolbar.appendChild(this.buildButtonGroup([
            { action: 'toggleSource', icon: 'bi-code-slash', title: labels.source },
        ]));

        return toolbar;
    };

    /**
     * @param {Object} labels
     * @returns {HTMLElement}
     */
    HtmlEditor.prototype.buildBlockSelect = function(labels) {
        const group = document.createElement('div');
        group.className = 'btn-group btn-group-sm me-1';

        const select = document.createElement('select');
        select.className = 'form-select form-select-sm html-editor-block-select';
        select.title = labels.blockFormat || '';

        const blocks = [
            ['p', labels.paragraph],
            ['h1', labels.heading1],
            ['h2', labels.heading2],
            ['h3', labels.heading3],
            ['h4', labels.heading4],
            ['h5', labels.heading5],
            ['h6', labels.heading6],
            ['div', labels.div],
            ['blockquote', labels.blockquote],
            ['pre', labels.pre],
        ];

        blocks.forEach(function(block) {
            const option = document.createElement('option');
            option.value = block[0];
            option.textContent = block[1] || block[0];
            select.appendChild(option);
        });

        const self = this;
        select.addEventListener('change', function() {
            self.focusBody();
            document.execCommand('formatBlock', false, '<' + select.value + '>');
            self.sync();
            self.refreshToolbarState();
        });

        this.blockSelect = select;
        group.appendChild(select);
        return group;
    };

    /**
     * @param {Array<Object>} buttons
     * @returns {HTMLElement}
     */
    HtmlEditor.prototype.buildButtonGroup = function(buttons) {
        const group = document.createElement('div');
        group.className = 'btn-group btn-group-sm me-1';

        const self = this;
        buttons.forEach(function(btn) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-outline-secondary';
            button.title = btn.title || '';
            button.innerHTML = '<i class="bi ' + btn.icon + '"></i>';

            button.addEventListener('click', function(e) {
                e.preventDefault();
                if (btn.cmd) {
                    self.execCommand(btn.cmd);
                } else if (btn.action) {
                    self.handleAction(btn.action);
                }
            });

            if (btn.cmd) {
                button.dataset.cmd = btn.cmd;
            }

            if (btn.action) {
                button.dataset.action = btn.action;
            }

            group.appendChild(button);
        });

        return group;
    };

    HtmlEditor.prototype.focusBody = function() {
        if (!this.sourceMode) {
            this.body.focus();
        }
    };

    /**
     * @param {string} cmd
     */
    HtmlEditor.prototype.execCommand = function(cmd) {
        this.focusBody();
        document.execCommand(cmd, false, null);
        this.sync();
        this.refreshToolbarState();
    };

    /**
     * @param {string} action
     */
    HtmlEditor.prototype.handleAction = function(action) {
        if (ALIGN_ACTIONS[action]) {
            this.setTextAlign(ALIGN_ACTIONS[action]);
            return;
        }

        switch (action) {
            case 'wrapCode':
                this.wrapCode();
                break;
            case 'insertLink':
                this.openLinkDialog();
                break;
            case 'insertImage':
                this.openImageDialog();
                break;
            case 'toggleSource':
                this.toggleSource();
                break;
        }
    };

    /**
     * @returns {HTMLElement|null}
     */
    HtmlEditor.prototype.getAlignmentBlock = function() {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return null;
        }

        let node = selection.anchorNode;
        if (node && node.nodeType === Node.TEXT_NODE) {
            node = node.parentNode;
        }

        while (node && node !== this.body) {
            if (BLOCK_TAGS.includes(node.nodeName)) {
                return node;
            }
            node = node.parentNode;
        }

        return null;
    };

    /**
     * @returns {string}
     */
    HtmlEditor.prototype.getCurrentTextAlign = function() {
        const block = this.getAlignmentBlock();
        if (!block) {
            return '';
        }

        if (block.style.textAlign) {
            return block.style.textAlign;
        }

        const alignAttr = block.getAttribute('align');
        return alignAttr ? alignAttr.toLowerCase() : '';
    };

    /**
     * @param {string} align
     */
    HtmlEditor.prototype.setTextAlign = function(align) {
        this.focusBody();
        const block = this.getAlignmentBlock();
        if (!block) {
            return;
        }

        const current = this.getCurrentTextAlign();
        block.removeAttribute('align');

        if (current === align) {
            block.style.removeProperty('text-align');
            if (!block.style.length) {
                block.removeAttribute('style');
            }
        } else {
            block.style.textAlign = align;
        }

        this.sync();
        this.refreshToolbarState();
    };

    HtmlEditor.prototype.wrapCode = function() {
        this.focusBody();
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return;
        }

        const range = selection.getRangeAt(0);
        if (range.collapsed) {
            return;
        }

        const code = document.createElement('code');
        try {
            range.surroundContents(code);
        } catch (e) {
            code.appendChild(range.extractContents());
            range.insertNode(code);
        }

        selection.removeAllRanges();
        this.sync();
        this.refreshToolbarState();
    };

    /**
     * @returns {string}
     */
    HtmlEditor.prototype.getSelectedText = function() {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return '';
        }
        return selection.toString();
    };

    HtmlEditor.prototype.saveSelection = function() {
        this.savedSelection = null;
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return;
        }

        const range = selection.getRangeAt(0);
        if (!this.body.contains(range.commonAncestorContainer)) {
            return;
        }

        this.savedSelection = range.cloneRange();
    };

    HtmlEditor.prototype.clearSavedSelection = function() {
        this.savedSelection = null;
    };

    /**
     * @returns {HTMLAnchorElement|null}
     */
    HtmlEditor.prototype.getLinkAtSelection = function() {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return null;
        }

        let node = selection.anchorNode;
        if (node && node.nodeType === Node.TEXT_NODE) {
            node = node.parentNode;
        }

        if (!node || !node.closest) {
            return null;
        }

        const link = node.closest('a');
        if (link && this.body.contains(link)) {
            return link;
        }

        return null;
    };

    /**
     * @param {HTMLAnchorElement} anchor
     * @returns {Object}
     */
    HtmlEditor.prototype.getLinkProperties = function(anchor) {
        return {
            url: anchor.getAttribute('href') || '',
            text: anchor.textContent || '',
            title: anchor.getAttribute('title') || '',
            target: anchor.getAttribute('target') || '',
        };
    };

    /**
     * @param {HTMLAnchorElement} anchor
     * @param {Object} props
     */
    HtmlEditor.prototype.applyLinkProperties = function(anchor, props) {
        const url = (props.url || '').trim();
        if (!url) {
            return;
        }

        anchor.href = url;

        const text = (props.text || '').trim();
        if (text) {
            anchor.textContent = text;
        } else if (!anchor.textContent) {
            anchor.textContent = url;
        }

        const title = (props.title || '').trim();
        if (title) {
            anchor.title = title;
        } else {
            anchor.removeAttribute('title');
        }

        const target = (props.target || '').trim();
        if (target) {
            anchor.target = target;
        } else {
            anchor.removeAttribute('target');
        }
    };

    /**
     * @param {Object} props
     */
    HtmlEditor.prototype.insertLinkElement = function(props) {
        const url = (props.url || '').trim();
        if (!url) {
            return;
        }

        if (this.editingLink) {
            this.applyLinkProperties(this.editingLink, props);
            this.editingLink = null;
            this.sync();
            this.refreshToolbarState();
            return;
        }

        this.focusBody();
        const anchor = document.createElement('a');
        this.applyLinkProperties(anchor, props);

        let range = null;
        if (this.savedSelection) {
            range = this.savedSelection.cloneRange();
            this.clearSavedSelection();
        } else {
            const selection = window.getSelection();
            if (selection && selection.rangeCount > 0) {
                range = selection.getRangeAt(0).cloneRange();
            }
        }

        if (range && this.body.contains(range.commonAncestorContainer)) {
            if (!range.collapsed) {
                range.deleteContents();
            }
            range.insertNode(anchor);
            range.setStartAfter(anchor);
            range.collapse(true);
            const selection = window.getSelection();
            if (selection) {
                selection.removeAllRanges();
                selection.addRange(range);
            }
        } else {
            this.body.appendChild(anchor);
        }

        this.sync();
        this.refreshToolbarState();
    };

    /**
     * @param {string} value
     * @returns {string}
     */
    HtmlEditor.prototype.normalizeCssSize = function(value) {
        value = value.trim();
        if (!value) {
            return '';
        }
        if (/^\d+(\.\d+)?$/.test(value)) {
            return value + 'px';
        }
        return value;
    };

    /**
     * @returns {HTMLImageElement|null}
     */
    HtmlEditor.prototype.getImageAtSelection = function() {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return null;
        }

        const range = selection.getRangeAt(0);

        if (range.startContainer.nodeName === 'IMG' && this.body.contains(range.startContainer)) {
            return range.startContainer;
        }

        if (range.endContainer.nodeName === 'IMG' && this.body.contains(range.endContainer)) {
            return range.endContainer;
        }

        let node = selection.anchorNode;
        if (node && node.nodeType === Node.TEXT_NODE) {
            node = node.parentNode;
        }

        if (!node) {
            return null;
        }

        if (node.nodeName === 'IMG' && this.body.contains(node)) {
            return node;
        }

        if (node.closest) {
            const img = node.closest('img');
            if (img && this.body.contains(img)) {
                return img;
            }
        }

        return null;
    };

    HtmlEditor.prototype.trackSelectedImage = function() {
        const img = this.getImageAtSelection();
        this.selectedImage = img || null;
    };

    /**
     * @returns {HTMLImageElement|null}
     */
    HtmlEditor.prototype.resolveEditingImage = function(img) {
        if (img && img.tagName === 'IMG') {
            return img;
        }

        return this.getImageAtSelection() || this.selectedImage;
    };

    /**
     * @param {HTMLImageElement} img
     * @returns {Object}
     */
    HtmlEditor.prototype.getImageProperties = function(img) {
        const props = {
            src: img.getAttribute('src') || '',
            alt: img.getAttribute('alt') || '',
            width: '',
            height: '',
            styles: '',
        };

        if (img.style.width) {
            props.width = img.style.width;
        } else if (img.getAttribute('width')) {
            props.width = img.getAttribute('width');
        }

        if (img.style.height) {
            props.height = img.style.height;
        } else if (img.getAttribute('height')) {
            props.height = img.getAttribute('height');
        }

        const styleParts = [];
        for (let i = 0; i < img.style.length; i++) {
            const name = img.style[i];
            if (name !== 'width' && name !== 'height') {
                styleParts.push(name + ': ' + img.style.getPropertyValue(name));
            }
        }
        props.styles = styleParts.join('; ');

        return props;
    };

    /**
     * @param {HTMLImageElement} img
     * @param {Object} props
     */
    HtmlEditor.prototype.applyImageProperties = function(img, props) {
        const src = (props.src || '').trim();
        if (!src) {
            return;
        }

        img.src = src;

        const alt = (props.alt || '').trim();
        if (alt) {
            img.alt = alt;
        } else {
            img.removeAttribute('alt');
        }

        img.removeAttribute('width');
        img.removeAttribute('height');

        const styleParts = [];
        const width = this.normalizeCssSize(props.width || '');
        const height = this.normalizeCssSize(props.height || '');
        const styles = (props.styles || '').trim().replace(/;+\s*$/, '');

        if (width) {
            styleParts.push('width: ' + width);
        }
        if (height) {
            styleParts.push('height: ' + height);
        }
        if (styles) {
            styleParts.push(styles);
        }

        if (styleParts.length) {
            img.style.cssText = styleParts.join('; ');
        } else {
            img.removeAttribute('style');
        }
    };

    /**
     * @param {Object} props
     */
    HtmlEditor.prototype.insertImageElement = function(props) {
        const src = (props.src || '').trim();
        if (!src) {
            return;
        }

        if (this.editingImage) {
            this.applyImageProperties(this.editingImage, props);
            this.editingImage = null;
            this.sync();
            this.refreshToolbarState();
            return;
        }

        this.focusBody();
        const img = document.createElement('img');
        this.applyImageProperties(img, props);

        const selection = window.getSelection();
        if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            range.deleteContents();
            range.insertNode(img);
            range.setStartAfter(img);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);
        } else {
            this.body.appendChild(img);
        }

        this.sync();
        this.refreshToolbarState();
    };

    HtmlEditor.prototype.ensureImageModal = function() {
        if (window.BrammoEditor.imageModal) {
            return window.BrammoEditor.imageModal;
        }

        const labels = this.options.labels || {};
        const modalEl = document.createElement('div');
        modalEl.id = 'editor-image-modal';
        modalEl.className = 'modal fade';
        modalEl.tabIndex = -1;
        modalEl.setAttribute('aria-hidden', 'true');
        modalEl.innerHTML =
            '<div class="modal-dialog modal-lg">' +
                '<div class="modal-content">' +
                    '<div class="modal-header">' +
                        '<h5 class="modal-title"></h5>' +
                        '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                    '</div>' +
                    '<div class="html-editor-image-form">' +
                        '<form class="modal-body">' +
                            '<div class="mb-3">' +
                                '<label class="form-label html-editor-image-src-label"></label>' +
                                '<div class="input-group">' +
                                    '<input type="text" class="form-control" name="src" required>' +
                                    '<button type="button" class="btn btn-outline-secondary html-editor-image-browse-btn">' +
                                        '<i class="bi bi-folder2-open"></i> ' +
                                        '<span class="html-editor-image-browse-label"></span>' +
                                    '</button>' +
                                '</div>' +
                            '</div>' +
                            '<div class="mb-3">' +
                                '<label class="form-label html-editor-image-alt-label"></label>' +
                                '<input type="text" class="form-control" name="alt">' +
                            '</div>' +
                            '<div class="row g-2 mb-3">' +
                                '<div class="col-sm-6">' +
                                    '<label class="form-label html-editor-image-width-label"></label>' +
                                    '<input type="text" class="form-control" name="width" placeholder="100">' +
                                '</div>' +
                                '<div class="col-sm-6">' +
                                    '<label class="form-label html-editor-image-height-label"></label>' +
                                    '<input type="text" class="form-control" name="height" placeholder="100">' +
                                '</div>' +
                            '</div>' +
                            '<div class="mb-0">' +
                                '<label class="form-label html-editor-image-styles-label"></label>' +
                                '<input type="text" class="form-control" name="styles" placeholder="margin: 1rem">' +
                            '</div>' +
                        '</form>' +
                        '<div class="modal-footer html-editor-image-footer">' +
                            '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"></button>' +
                            '<button type="button" class="btn btn-primary html-editor-image-insert"></button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="html-editor-image-browse-panel d-none">' +
                        '<div class="modal-body pb-0">' +
                            '<button type="button" class="btn btn-sm btn-outline-secondary html-editor-image-browse-back">' +
                                '<i class="bi bi-arrow-left"></i> ' +
                                '<span class="html-editor-image-browse-back-label"></span>' +
                            '</button>' +
                        '</div>' +
                        '<div class="html-editor-image-browse-body px-3 pb-3"></div>' +
                    '</div>' +
                '</div>' +
            '</div>';

        modalEl.querySelector('.modal-title').textContent = labels.imageDialogTitle || 'Insert image';
        modalEl.querySelector('.html-editor-image-src-label').textContent = labels.imageSrc || 'URL';
        modalEl.querySelector('.html-editor-image-alt-label').textContent = labels.imageAlt || 'Alt';
        modalEl.querySelector('.html-editor-image-width-label').textContent = labels.imageWidth || 'Width';
        modalEl.querySelector('.html-editor-image-height-label').textContent = labels.imageHeight || 'Height';
        modalEl.querySelector('.html-editor-image-styles-label').textContent = labels.imageStyles || 'Styles';
        modalEl.querySelector('.html-editor-image-browse-label').textContent = labels.imageSelect || 'Select';
        modalEl.querySelector('.html-editor-image-browse-back-label').textContent = labels.imageBack || 'Back';
        modalEl.querySelector('.html-editor-image-footer .btn-secondary').textContent = labels.cancel || 'Cancel';
        modalEl.querySelector('.html-editor-image-insert').textContent = labels.imageInsert || 'Insert';

        document.body.appendChild(modalEl);

        const form = modalEl.querySelector('form');
        const formView = modalEl.querySelector('.html-editor-image-form');
        const browseView = modalEl.querySelector('.html-editor-image-browse-panel');
        const browseBtn = modalEl.querySelector('.html-editor-image-browse-btn');
        const browseBackBtn = modalEl.querySelector('.html-editor-image-browse-back');
        const insertBtn = modalEl.querySelector('.html-editor-image-insert');
        const closeBtn = modalEl.querySelector('.modal-header .btn-close');
        const modalTitle = modalEl.querySelector('.modal-title');
        const bsModal = typeof bootstrap !== 'undefined' ? new bootstrap.Modal(modalEl, {
            backdrop: 'static',
            keyboard: false,
        }) : null;

        browseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const editor = window.BrammoEditor.activeImageEditor;
            if (editor) {
                editor.openImagePickerForDialog();
            }
        });

        browseBackBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const editor = window.BrammoEditor.activeImageEditor;
            if (editor) {
                editor.showImageFormView();
            }
        });

        closeBtn.addEventListener('click', function(e) {
            const editor = window.BrammoEditor.activeImageEditor;
            if (editor && editor.imageBrowseEmbedded) {
                e.preventDefault();
                e.stopImmediatePropagation();
                editor.showImageFormView();
            }
        });

        modalEl.addEventListener('hide.bs.modal', function(e) {
            const editor = window.BrammoEditor.activeImageEditor;
            if (editor && editor.imageBrowseEmbedded) {
                e.preventDefault();
                editor.showImageFormView();
            }
        });

        insertBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const editor = window.BrammoEditor.activeImageEditor;
            if (!editor) {
                return;
            }

            const src = form.elements.src.value.trim();
            if (!src) {
                form.elements.src.focus();
                return;
            }

            editor.insertImageElement({
                src: src,
                alt: form.elements.alt.value,
                width: form.elements.width.value,
                height: form.elements.height.value,
                styles: form.elements.styles.value,
            });

            if (bsModal) {
                bsModal.hide();
            }
        });

        modalEl.addEventListener('hidden.bs.modal', function() {
            const editor = window.BrammoEditor.activeImageEditor;
            if (editor) {
                editor.imageDialogOpen = false;
                editor.imageBrowseEmbedded = false;
                editor.editingImage = null;
                editor.showImageFormView();
            }
            window.BrammoEditor.activeImageEditor = null;
            form.reset();
        });

        window.BrammoEditor.imageModal = {
            element: modalEl,
            form: form,
            formView: formView,
            browseView: browseView,
            modalTitle: modalTitle,
            insertBtn: insertBtn,
            modal: bsModal,
            browseSelector: '#editor-image-modal .html-editor-image-browse-body',
        };

        return window.BrammoEditor.imageModal;
    };

    HtmlEditor.prototype.updateImageModalLabels = function() {
        const imageModal = window.BrammoEditor.imageModal;
        if (!imageModal) {
            return;
        }

        const labels = this.options.labels || {};
        const editing = !!this.editingImage;

        imageModal.modalTitle.textContent = editing
            ? (labels.imageEditTitle || 'Edit image')
            : (labels.imageDialogTitle || 'Insert image');
        imageModal.insertBtn.textContent = editing
            ? (labels.imageSave || 'Save')
            : (labels.imageInsert || 'Insert');
    };

    HtmlEditor.prototype.openImageDialog = function(img) {
        const imageModal = this.ensureImageModal();
        window.BrammoEditor.activeImageEditor = this;
        this.imageDialogOpen = true;
        this.editingImage = this.resolveEditingImage(img);
        this.showImageFormView();
        imageModal.form.reset();

        if (this.editingImage) {
            const props = this.getImageProperties(this.editingImage);
            imageModal.form.elements.src.value = props.src;
            imageModal.form.elements.alt.value = props.alt;
            imageModal.form.elements.width.value = props.width;
            imageModal.form.elements.height.value = props.height;
            imageModal.form.elements.styles.value = props.styles;
        }

        this.updateImageModalLabels();

        if (imageModal.modal) {
            imageModal.modal.show();
            imageModal.form.elements.src.focus();
        }
    };

    HtmlEditor.prototype.setImageDialogUrl = function(url) {
        const imageModal = window.BrammoEditor.imageModal;
        if (!imageModal) {
            return;
        }
        imageModal.form.elements.src.value = url;
    };

    HtmlEditor.prototype.showImageFormView = function() {
        const imageModal = window.BrammoEditor.imageModal;
        if (!imageModal) {
            return;
        }

        this.imageBrowseEmbedded = false;
        imageModal.formView.classList.remove('d-none');
        imageModal.browseView.classList.add('d-none');
        this.updateImageModalLabels();
    };

    HtmlEditor.prototype.showImageBrowseView = function() {
        const imageModal = window.BrammoEditor.imageModal;
        if (!imageModal) {
            return;
        }

        this.imageBrowseEmbedded = true;
        imageModal.formView.classList.add('d-none');
        imageModal.browseView.classList.remove('d-none');
        imageModal.modalTitle.textContent = this.options.labels.imageBrowseTitle || 'Select Image';
    };

    HtmlEditor.prototype.openImagePickerForDialog = function() {
        if (!this.fileBrowser) {
            return;
        }

        const imageModal = window.BrammoEditor.imageModal;
        if (!imageModal) {
            return;
        }

        this.fileBrowser.setEmbeddedSelector(imageModal.browseSelector);

        const folder = this.options.folder || 'images';
        const target = this.imageTarget.id;
        const url = this.options.browseUrl + '?folder=' + encodeURIComponent(folder) +
            '&target=' + encodeURIComponent(target);

        this.showImageBrowseView();
        this.fileBrowser.loadContent(url, imageModal.browseSelector);
    };

    HtmlEditor.prototype.ensureLinkModal = function() {
        if (window.BrammoEditor.linkModal) {
            return window.BrammoEditor.linkModal;
        }

        const labels = this.options.labels || {};
        const modalEl = document.createElement('div');
        modalEl.id = 'editor-link-modal';
        modalEl.className = 'modal fade';
        modalEl.tabIndex = -1;
        modalEl.setAttribute('aria-hidden', 'true');
        modalEl.innerHTML =
            '<div class="modal-dialog modal-lg">' +
                '<div class="modal-content">' +
                    '<div class="modal-header">' +
                        '<h5 class="modal-title"></h5>' +
                        '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                    '</div>' +
                    '<div class="html-editor-link-form">' +
                        '<form class="modal-body">' +
                            '<div class="mb-3">' +
                                '<label class="form-label html-editor-link-url-label"></label>' +
                                '<div class="input-group">' +
                                    '<input type="text" class="form-control" name="url" required>' +
                                    '<button type="button" class="btn btn-outline-secondary html-editor-link-browse-btn">' +
                                        '<i class="bi bi-folder2-open"></i> ' +
                                        '<span class="html-editor-link-browse-label"></span>' +
                                    '</button>' +
                                '</div>' +
                            '</div>' +
                            '<div class="mb-3">' +
                                '<label class="form-label html-editor-link-text-label"></label>' +
                                '<input type="text" class="form-control" name="text">' +
                            '</div>' +
                            '<div class="mb-3">' +
                                '<label class="form-label html-editor-link-title-label"></label>' +
                                '<input type="text" class="form-control" name="title">' +
                            '</div>' +
                            '<div class="mb-0">' +
                                '<label class="form-label html-editor-link-target-label"></label>' +
                                '<select class="form-select" name="target">' +
                                    '<option value=""></option>' +
                                    '<option value="_blank"></option>' +
                                    '<option value="_self"></option>' +
                                    '<option value="_parent"></option>' +
                                    '<option value="_top"></option>' +
                                '</select>' +
                            '</div>' +
                        '</form>' +
                        '<div class="modal-footer html-editor-link-footer">' +
                            '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"></button>' +
                            '<button type="button" class="btn btn-primary html-editor-link-insert"></button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="html-editor-link-browse-panel d-none">' +
                        '<div class="modal-body pb-0">' +
                            '<button type="button" class="btn btn-sm btn-outline-secondary html-editor-link-browse-back">' +
                                '<i class="bi bi-arrow-left"></i> ' +
                                '<span class="html-editor-link-browse-back-label"></span>' +
                            '</button>' +
                        '</div>' +
                        '<div class="html-editor-link-browse-body px-3 pb-3"></div>' +
                    '</div>' +
                '</div>' +
            '</div>';

        modalEl.querySelector('.modal-title').textContent = labels.linkDialogTitle || 'Insert link';
        modalEl.querySelector('.html-editor-link-url-label').textContent = labels.linkUrl || 'URL';
        modalEl.querySelector('.html-editor-link-text-label').textContent = labels.linkText || 'Text';
        modalEl.querySelector('.html-editor-link-title-label').textContent = labels.linkTitle || 'Title';
        modalEl.querySelector('.html-editor-link-target-label').textContent = labels.linkTarget || 'Target';
        modalEl.querySelector('.html-editor-link-browse-label').textContent = labels.linkSelect || 'Select';
        modalEl.querySelector('.html-editor-link-browse-back-label').textContent = labels.linkBack || 'Back';
        modalEl.querySelector('.html-editor-link-footer .btn-secondary').textContent = labels.cancel || 'Cancel';
        modalEl.querySelector('.html-editor-link-insert').textContent = labels.linkInsert || 'Insert';

        const targetSelect = modalEl.querySelector('select[name="target"]');
        targetSelect.options[0].textContent = labels.linkTargetDefault || 'Same window';
        targetSelect.options[1].textContent = labels.linkTargetBlank || 'New window (_blank)';
        targetSelect.options[2].textContent = labels.linkTargetSelf || '_self';
        targetSelect.options[3].textContent = labels.linkTargetParent || '_parent';
        targetSelect.options[4].textContent = labels.linkTargetTop || '_top';

        document.body.appendChild(modalEl);

        const form = modalEl.querySelector('form');
        const formView = modalEl.querySelector('.html-editor-link-form');
        const browseView = modalEl.querySelector('.html-editor-link-browse-panel');
        const browseBtn = modalEl.querySelector('.html-editor-link-browse-btn');
        const browseBackBtn = modalEl.querySelector('.html-editor-link-browse-back');
        const insertBtn = modalEl.querySelector('.html-editor-link-insert');
        const closeBtn = modalEl.querySelector('.modal-header .btn-close');
        const modalTitle = modalEl.querySelector('.modal-title');
        const bsModal = typeof bootstrap !== 'undefined' ? new bootstrap.Modal(modalEl, {
            backdrop: 'static',
            keyboard: false,
        }) : null;

        browseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const editor = window.BrammoEditor.activeLinkEditor;
            if (editor) {
                editor.openLinkPickerForDialog();
            }
        });

        browseBackBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const editor = window.BrammoEditor.activeLinkEditor;
            if (editor) {
                editor.showLinkFormView();
            }
        });

        closeBtn.addEventListener('click', function(e) {
            const editor = window.BrammoEditor.activeLinkEditor;
            if (editor && editor.linkBrowseEmbedded) {
                e.preventDefault();
                e.stopImmediatePropagation();
                editor.showLinkFormView();
            }
        });

        modalEl.addEventListener('hide.bs.modal', function(e) {
            const editor = window.BrammoEditor.activeLinkEditor;
            if (editor && editor.linkBrowseEmbedded) {
                e.preventDefault();
                editor.showLinkFormView();
            }
        });

        insertBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const editor = window.BrammoEditor.activeLinkEditor;
            if (!editor) {
                return;
            }

            const url = form.elements.url.value.trim();
            if (!url) {
                form.elements.url.focus();
                return;
            }

            editor.insertLinkElement({
                url: url,
                text: form.elements.text.value,
                title: form.elements.title.value,
                target: form.elements.target.value,
            });

            if (bsModal) {
                bsModal.hide();
            }
        });

        modalEl.addEventListener('hidden.bs.modal', function() {
            const editor = window.BrammoEditor.activeLinkEditor;
            if (editor) {
                editor.linkDialogOpen = false;
                editor.linkBrowseEmbedded = false;
                editor.editingLink = null;
                editor.clearSavedSelection();
                editor.showLinkFormView();
            }
            window.BrammoEditor.activeLinkEditor = null;
            form.reset();
        });

        window.BrammoEditor.linkModal = {
            element: modalEl,
            form: form,
            formView: formView,
            browseView: browseView,
            modalTitle: modalTitle,
            insertBtn: insertBtn,
            modal: bsModal,
            browseSelector: '#editor-link-modal .html-editor-link-browse-body',
        };

        return window.BrammoEditor.linkModal;
    };

    HtmlEditor.prototype.updateLinkModalLabels = function() {
        const linkModal = window.BrammoEditor.linkModal;
        if (!linkModal) {
            return;
        }

        const labels = this.options.labels || {};
        const editing = !!this.editingLink;

        linkModal.modalTitle.textContent = editing
            ? (labels.linkEditTitle || 'Edit link')
            : (labels.linkDialogTitle || 'Insert link');
        linkModal.insertBtn.textContent = editing
            ? (labels.linkSave || 'Save')
            : (labels.linkInsert || 'Insert');
    };

    HtmlEditor.prototype.openLinkDialog = function(anchor) {
        const linkModal = this.ensureLinkModal();
        window.BrammoEditor.activeLinkEditor = this;
        this.linkDialogOpen = true;
        this.clearSavedSelection();
        this.editingLink = anchor && anchor.tagName === 'A' ? anchor : this.getLinkAtSelection();
        this.showLinkFormView();
        linkModal.form.reset();

        if (this.editingLink) {
            const props = this.getLinkProperties(this.editingLink);
            linkModal.form.elements.url.value = props.url;
            linkModal.form.elements.text.value = props.text;
            linkModal.form.elements.title.value = props.title;
            linkModal.form.elements.target.value = props.target;
        } else {
            linkModal.form.elements.text.value = this.getSelectedText();
            this.saveSelection();
        }

        this.updateLinkModalLabels();

        if (linkModal.modal) {
            linkModal.modal.show();
            linkModal.form.elements.url.focus();
        }
    };

    HtmlEditor.prototype.setLinkDialogUrl = function(url) {
        const linkModal = window.BrammoEditor.linkModal;
        if (!linkModal) {
            return;
        }
        linkModal.form.elements.url.value = url;
    };

    HtmlEditor.prototype.showLinkFormView = function() {
        const linkModal = window.BrammoEditor.linkModal;
        if (!linkModal) {
            return;
        }

        this.linkBrowseEmbedded = false;
        linkModal.formView.classList.remove('d-none');
        linkModal.browseView.classList.add('d-none');
        this.updateLinkModalLabels();
    };

    HtmlEditor.prototype.showLinkBrowseView = function() {
        const linkModal = window.BrammoEditor.linkModal;
        if (!linkModal) {
            return;
        }

        this.linkBrowseEmbedded = true;
        linkModal.formView.classList.add('d-none');
        linkModal.browseView.classList.remove('d-none');
        linkModal.modalTitle.textContent = this.options.labels.linkBrowseTitle || 'Select file';
    };

    HtmlEditor.prototype.openLinkPickerForDialog = function() {
        if (!this.fileBrowser) {
            return;
        }

        const linkModal = window.BrammoEditor.linkModal;
        if (!linkModal) {
            return;
        }

        this.fileBrowser.setEmbeddedSelector(linkModal.browseSelector);

        const folder = this.options.linkFolder || 'files';
        const target = this.linkTarget.id;
        const url = this.options.filesBrowseUrl + '?folder=' + encodeURIComponent(folder) +
            '&target=' + encodeURIComponent(target);

        this.showLinkBrowseView();
        this.fileBrowser.loadContent(url, linkModal.browseSelector);
    };

    HtmlEditor.prototype.toggleSource = function() {
        if (this.sourceMode) {
            this.body.innerHTML = this.textarea.value;
            this.textarea.style.display = 'none';
            this.textarea.setAttribute('aria-hidden', 'true');
            this.body.hidden = false;
            this.body.contentEditable = 'true';
            this.sourceMode = false;
            this.refreshToolbarState();
        } else {
            this.sync();
            this.textarea.style.display = '';
            this.textarea.removeAttribute('aria-hidden');
            this.body.hidden = true;
            this.body.contentEditable = 'false';
            this.sourceMode = true;
            this.textarea.focus();
        }

        this.updateToolbarState();
    };

    HtmlEditor.prototype.onSelectionChange = function() {
        if (this.sourceMode) {
            return;
        }

        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return;
        }

        const node = selection.anchorNode;
        if (!node || !this.body.contains(node)) {
            return;
        }

        this.trackSelectedImage();
        this.refreshToolbarState();
    };

    /**
     * @returns {string}
     */
    HtmlEditor.prototype.getBlockTag = function() {
        const selection = window.getSelection();

        if (selection && selection.rangeCount > 0) {
            let node = selection.anchorNode;
            if (node && node.nodeType === Node.TEXT_NODE) {
                node = node.parentNode;
            }

            while (node && node !== this.body) {
                if (FORMAT_BLOCK_TAGS.includes(node.nodeName)) {
                    return node.nodeName.toLowerCase();
                }
                node = node.parentNode;
            }
        }

        let block = document.queryCommandValue('formatBlock') || 'p';
        block = block.replace(/[<>]/g, '').toLowerCase();

        return FORMAT_BLOCK_TAGS.map(function(tag) {
            return tag.toLowerCase();
        }).includes(block) ? block : 'p';
    };

    /**
     * @returns {boolean}
     */
    HtmlEditor.prototype.isSelectionInCode = function() {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return false;
        }

        let node = selection.anchorNode;
        if (node && node.nodeType === Node.TEXT_NODE) {
            node = node.parentNode;
        }

        while (node && node !== this.body) {
            if (node.nodeName === 'CODE') {
                return true;
            }
            node = node.parentNode;
        }

        return false;
    };

    HtmlEditor.prototype.refreshToolbarState = function() {
        if (this.sourceMode) {
            return;
        }

        if (this.blockSelect) {
            this.blockSelect.value = this.getBlockTag();
        }

        const currentAlign = this.getCurrentTextAlign();
        this.toolbar.querySelectorAll('button[data-action]').forEach(function(btn) {
            const align = ALIGN_ACTIONS[btn.dataset.action];
            if (!align) {
                return;
            }
            const active = currentAlign === align;
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        this.toolbar.querySelectorAll('button[data-cmd]').forEach(function(btn) {
            const cmd = btn.dataset.cmd;

            if (cmd === 'undo' || cmd === 'redo') {
                let enabled = false;
                try {
                    enabled = document.queryCommandEnabled(cmd);
                } catch (e) {
                    enabled = false;
                }
                btn.disabled = !enabled;
                return;
            }

            let active = false;
            try {
                active = document.queryCommandState(cmd);
            } catch (e) {
                active = false;
            }
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        const codeBtn = this.toolbar.querySelector('button[data-action="wrapCode"]');
        if (codeBtn) {
            const inCode = this.isSelectionInCode();
            codeBtn.classList.toggle('active', inCode);
            codeBtn.setAttribute('aria-pressed', inCode ? 'true' : 'false');
        }
    };

    HtmlEditor.prototype.updateToolbarState = function() {
        const disable = this.sourceMode;
        this.toolbar.querySelectorAll('button, select').forEach(function(el) {
            el.disabled = disable && el.dataset.action !== 'toggleSource';
        });

        if (disable) {
            this.toolbar.querySelectorAll('button.active').forEach(function(btn) {
                btn.classList.remove('active');
                btn.setAttribute('aria-pressed', 'false');
            });
        } else {
            this.refreshToolbarState();
        }

        const sourceBtn = this.toolbar.querySelector('button[data-action="toggleSource"]');
        if (sourceBtn) {
            sourceBtn.classList.toggle('active', disable);
            sourceBtn.setAttribute('aria-pressed', disable ? 'true' : 'false');
        }
    };

    HtmlEditor.prototype.sync = function() {
        if (!this.sourceMode) {
            this.textarea.value = this.body.innerHTML;
        }
    };

    return HtmlEditor;
})();

window.BrammoEditor = window.BrammoEditor || { instances: {} };
window.BrammoEditor.HtmlEditor = HtmlEditor;
