<?php
/**
 * Form image element
 * 
 * @var \Brammo\Admin\View\AppView $this
 * @var \Cake\Orm\Entity $entity
 */

use Cake\Routing\Router;

if (!isset($entity)) {
    throw new InvalidArgumentException('Entity variable is required for Form image element');
    return;
}

$defaultOptions = [
    'field' => 'image',
    'folder' => 'images',
    'label' => __d('brammo/admin', 'Image'),
    'allowEmpty' => true
];

foreach ($defaultOptions as $key => $value) {
    if (!isset($$key)) {
        $$key = $value;
    }
}

$filename = '';
if (!empty($entity->{$field})) {
    $filename = $entity->{$field};
    $folder = ltrim(dirname($filename), '/');
}

$imagesUrl = Router::url([
    'plugin' => 'Brammo/Admin',
    'controller' => 'FileManager',
    'action' => 'images'
]);

$uploadUrl = Router::url([
    'plugin' => 'Brammo/Admin',
    'controller' => 'FileManager',
    'action' => 'upload'
]);

$this->Html->script('Brammo/Admin.filemanager', ['block' => true]);
?>
<div class="mb-3 p-2 border rounded<?php if (!$allowEmpty) { ?> required<?php } ?>">
    <?php if ($label): ?>
        <label class="form-label"><?= $label ?></label>
    <?php endif ?>
    <div class="form-image" id="form-image-<?= $field ?>" data-target="input-<?= $field ?>"<?php if (!empty($folder)) { ?> data-folder="<?= $folder ?>"<?php } ?>>
        <div class="mb-2"><img src="<?= $filename ?>" alt="" id="image-<?= $field ?>" class="mw-100"></div>
        <div class="mb-2 p-1 bg-light text-muted small filename"><?= $filename ?></div>
        <div class="actions">
            <a href="#" class="btn btn-info btn-sm" id="select-image-<?= $field ?>">
                <?= $this->Html->icon('folder2-open') ?>
                <?= __d('brammo/admin', 'Select') ?>
            </a> 
            <label class="btn btn-primary btn-sm mb-0" id="upload-image-<?= $field ?>">
                <?= $this->Html->icon('upload') ?>
                <?= __d('brammo/admin', 'Upload') ?>
                <input type="file" accept="image/*" style="display:none" id="upload-input-<?= $field ?>">
            </label> 
            <a href="#" title="<?= __d('brammo/admin', 'Delete') ?>" class="btn btn-danger btn-sm delete" id="delete-image-<?= $field ?>"<?php if (empty($filename)) { ?> style="display:none"<?php } ?>><?= $this->Html->icon('trash') ?></a>
        </div>
    </div>
    <input type="hidden" name="<?= $field ?>" value="<?= $filename ?>" id="input-<?= $field ?>">
</div>
<?php $this->append('script') ?>
    <script>
        const formImage = document.getElementById('form-image-<?= $field ?>');
        const selectBtn = document.getElementById('select-image-<?= $field ?>');
        const deleteBtn = document.getElementById('delete-image-<?= $field ?>');
        selectBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const existingModal = document.getElementById('modal-filemanager');
            if (existingModal) {
                existingModal.remove();
            }
            fetch('<?= $imagesUrl ?>?folder=' + formImage.dataset.folder + '&target=' + formImage.dataset.target + '&modal=1', {
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
            });
        });
        deleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const target = formImage.dataset.target;
            const element = document.getElementById(target);
            element.value = '';
            formImage.querySelector('img').src = '';
            formImage.querySelector('.filename').textContent = '';
            this.style.display = 'none';
        });
        const uploadInput = document.getElementById('upload-input-<?= $field ?>');
        uploadInput.addEventListener('change', function(e) {
            const file = this.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('file', file);
            fetch('<?= $uploadUrl ?>?folder=' + formImage.dataset.folder, {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': '<?= $this->request->getAttribute('csrfToken') ?>',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                if (data.files && data.files.length > 0) {
                    const uploadedFile = data.files[0];
                    const target = formImage.dataset.target;
                    const element = document.getElementById(target);
                    const filename = '/' + formImage.dataset.folder + '/' + uploadedFile;
                    element.value = filename;
                    formImage.querySelector('img').src = filename;
                    formImage.querySelector('.filename').textContent = uploadedFile;
                    deleteBtn.style.display = '';
                }
            })
            .catch(error => {
                alert('<?= __d('brammo/admin', 'Upload failed') ?>');
                console.error(error);
            });
            this.value = '';
        });
    </script>
<?php $this->end()?>
