<?php
/**
 * Form image element
 *
 * @var \Brammo\Admin\View\AppView $this
 * @var string $field
 * @var string $folder
 * @var string $label
 * @var bool $allowEmpty
 * @var string|null $value
 */

use Cake\Routing\Router;
use function Cake\Core\h;

// Apply defaults for optional variables
$field = $field ?? 'image';
$folder = $folder ?? 'images';
$label = $label ?? __d('brammo/admin', 'Image');
$allowEmpty = $allowEmpty ?? true;
$value = $value ?? null;

// Determine current image folder from value
if ($value) {
    $folder = ltrim(dirname($value), '/');
}

// Ensure folder is within images
if (!str_starts_with($folder, 'images')) {
    $folder = 'images';
}

// Load FileManager JS

// Generate URLs for image selection and upload
$browseUrl = Router::url([
    'plugin' => 'Brammo/Admin',
    'controller' => 'FileManager',
    'action' => 'browseImages',
]);

$uploadUrl = Router::url([
    'plugin' => 'Brammo/Admin',
    'controller' => 'FileManager',
    'action' => 'upload',
]);

$id = uniqid();

$this->Html->script('Brammo/Admin.file-browser', ['block' => true]);
$this->Html->script('Brammo/Admin.form-image', ['block' => true]);
?>
<div class="mb-3 p-2 border rounded<?php if (!$allowEmpty) {
    ?> required<?php
                                   } ?>">
    <?php if ($label) : ?>
        <label class="form-label"><?= h($label) ?></label>
    <?php endif ?>
    <div class="form-image" id="form-image-<?= $id ?>"<?php if (!empty($folder)) {
        ?> data-folder="<?= h($folder) ?>"<?php
                                           } ?>>
        <div class="mb-2 image-preview">
            <img src="<?= h($value) ?>" alt="" class="mw-100">
        </div>
        <div class="mb-2 p-1 bg-light text-muted small filename"<?php if (empty($value)) {
            ?> style="display:none"<?php
                                                                } ?>><?= h($value) ?></div>
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
                <?php if (empty($value)) {
                    ?> style="display:none"<?php
                } ?>>
                <?= $this->Html->icon('trash') ?>
            </a>
        </div>
        <input type="hidden" name="<?= h($field) ?>" value="<?= h($value) ?>">
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
