<?php
/**
 * Form Editor element
 *
 * Uses Summernote editor with FileManager integration for image selection.
 *
 * @var \Brammo\Admin\View\AppView $this
 */

use Cake\Routing\Router;

$imagesUrl = Router::url([
    'plugin' => 'Brammo/Admin',
    'controller' => 'FileManager',
    'action' => 'images',
]);

$uploadUrl = Router::url([
    'plugin' => 'Brammo/Admin',
    'controller' => 'FileManager',
    'action' => 'upload',
]);

// Load Summernote CSS
$this->Html->css('https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.css', ['block' => true]);

// Load Summernote JS
$this->Html->script('https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.js', ['block' => true]);
?>
<?php $this->append('script') ?>
<script>
(function() {
    const imagesUrl = '<?= $imagesUrl ?>';
    const uploadUrl = '<?= $uploadUrl ?>';

    // Custom button to open FileManager
    const FileManagerButton = function(context) {
        const ui = $.summernote.ui;
        const button = ui.button({
            contents: '<i class="bi bi-folder2-open"></i>',
            tooltip: '<?= __d('brammo/admin', 'Browse Images') ?>',
            click: function() {
                const $editor = context.$note;

                // Remove existing modal if present
                const existingModal = document.getElementById('modal-filemanager');
                if (existingModal) {
                    existingModal.remove();
                }

                // Fetch FileManager modal
                fetch(imagesUrl + '?modal=1', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    const modalDiv = document.createElement('div');
                    modalDiv.id = 'modal-filemanager';
                    modalDiv.className = 'modal fade';
                    modalDiv.innerHTML = html;
                    document.body.appendChild(modalDiv);

                    const modal = new bootstrap.Modal(modalDiv);
                    modal.show();

                    // Listen for image selection
                    modalDiv.addEventListener('click', function(e) {
                        if (e.target.classList.contains('select-image') || e.target.closest('.select-image')) {
                            e.preventDefault();
                            const link = e.target.classList.contains('select-image') ? e.target : e.target.closest('.select-image');
                            const imageSrc = link.dataset.image;
                            if (imageSrc) {
                                $editor.summernote('insertImage', imageSrc);
                                modal.hide();
                            }
                        }
                    });

                    modalDiv.addEventListener('hidden.bs.modal', function() {
                        modalDiv.remove();
                    });
                });
            }
        });

        return button.render();
    };

    // Initialize Summernote on all .editor textareas
    $(document).ready(function() {
        $('.editor').summernote({
            height: 400,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'filemanager']],
                ['view', ['fullscreen', 'codeview']]
            ],
            buttons: {
                filemanager: FileManagerButton
            },
            callbacks: {
                onImageUpload: function(files) {
                    const $editor = $(this);

                    // Upload each file
                    for (let i = 0; i < files.length; i++) {
                        const formData = new FormData();
                        formData.append('file', files[i]);

                        fetch(uploadUrl, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-Token': document.querySelector('meta[name="csrfToken"]')?.content || ''
                            },
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.url) {
                                $editor.summernote('insertImage', data.url);
                            } else {
                                alert(data.error || '<?= __d('brammo/admin', 'Upload failed') ?>');
                            }
                        })
                        .catch(error => {
                            console.error('Upload error:', error);
                            alert('<?= __d('brammo/admin', 'Upload failed') ?>');
                        });
                    }
                }
            },
            styleTags: ['p', 'h2', 'h3', 'h4', 'h5', 'h6'],
            popover: {
                image: [
                    ['image', ['resizeFull', 'resizeHalf', 'resizeQuarter', 'resizeNone']],
                    ['float', ['floatLeft', 'floatRight', 'floatNone']],
                    ['remove', ['removeMedia']]
                ],
                link: [
                    ['link', ['linkDialogShow', 'unlink']]
                ],
                table: [
                    ['add', ['addRowDown', 'addRowUp', 'addColLeft', 'addColRight']],
                    ['delete', ['deleteRow', 'deleteCol', 'deleteTable']]
                ]
            }
        });
    });
})();
</script>
<?php $this->end() ?>
