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
}

if (!is_a($entity, 'Cake\Orm\Entity')) {
    throw new InvalidArgumentException('Entity variable must be an instance of Cake\Orm\Entity');
}

// Set default options
$defaultOptions = [
    'field' => 'image',
    'folder' => 'images',
    'label' => __d('brammo/admin', 'Image'),
    'allowEmpty' => true
];

// Apply default options
foreach ($defaultOptions as $key => $value) {
    if (!isset($$key)) {
        $$key = $value;
    }
}

// Check if entity has the field
if (!$entity->has($field)) {
    throw new InvalidArgumentException(sprintf('Entity does not have the field "%s"', $field));
}

// Determine current image filename and folder
$filename = $entity->get($field);
if ($filename) {
    $folder = ltrim(dirname($filename), '/');
}

// check if folder start with images
if (strpos($folder, 'images') !== 0) {
    $folder = 'images';
}

// Load FileManager JS

// Generate URLs for image selection and upload
$browseUrl = Router::url([
    'plugin' => 'Brammo/Admin',
    'controller' => 'FileManager',
    'action' => 'browseImages'
]);

$uploadUrl = Router::url([
    'plugin' => 'Brammo/Admin',
    'controller' => 'FileManager',
    'action' => 'upload'
]);

$id = uniqid();

$this->Html->script('Brammo/Admin.file-browser', ['block' => true]);
$this->Html->script('Brammo/Admin.form-image', ['block' => true]);
?>
<div class="mb-3 p-2 border rounded<?php if (!$allowEmpty) { ?> required<?php } ?>">
    <?php if ($label): ?>
        <label class="form-label"><?= $label ?></label>
    <?php endif ?>
    <div class="form-image" id="form-image-<?= $id ?>"<?php if (!empty($folder)) { ?> data-folder="<?= $folder ?>"<?php } ?>>
        <div class="mb-2 image-preview">
            <img src="<?= $filename ?>" alt="" class="mw-100">
        </div>
        <div class="mb-2 p-1 bg-light text-muted small filename"<?php if (empty($filename)) { ?> style="display:none"<?php } ?>><?= $filename ?></div>
        <div class="actions">
            <a href="#" class="btn btn-info btn-sm select">
                <?= $this->Html->icon('folder2-open') ?>
                <?= __d('brammo/admin', 'Select') ?>
            </a> 
            <label class="btn btn-primary btn-sm mb-0 upload">
                <?= $this->Html->icon('upload') ?>
                <?= __d('brammo/admin', 'Upload') ?>
                <input type="file" accept="image/*" style="display:none">
            </label> 
            <a href="#" title="<?= __d('brammo/admin', 'Delete') ?>" class="btn btn-danger btn-sm delete" 
                <?php if (empty($filename)) { ?> style="display:none"<?php } ?>>
                <?= $this->Html->icon('trash') ?>
            </a>
        </div>
        <input type="hidden" name="<?= $field ?>" value="<?= $filename ?>">
    </div>
</div>
<?php $this->append('script') ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formImage<?= $id ?> = new FormImage(
                'form-image-<?= $id ?>', 
                '<?= $browseUrl ?>', 
                '<?= $uploadUrl ?>',
                '<?= __d('brammo/admin', 'Select Image') ?>'
            );
        });
    </script>
<?php $this->end()?>
