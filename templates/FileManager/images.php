<?php
/**
 * FileManager images template
 * 
 * @var \Brammo\Admin\View\AppView $this
 * @var string $folder
 * @var string $target
 * @var string $filter
 */

$this->assign('title', __d('brammo/admin', 'File Manager'));

$path = '/';
if ($folder) {
    $path .= $folder . '/';
}

$showModal = $this->request->getQuery('modal') == '1';

if (!$this->request->is('ajax')) {
    $this->Html->script('Brammo/Admin.filemanager', ['block' => true]);
}
?>
<?php $this->start('content'); ?>
    <?= $this->element('FileManager/header', ['folder' => $folder, 'filter' => $filter, 'showUpload' => true]) ?>
    <div class="filemanager-images row mt-3 g-2">
        <?php foreach ($items as $item) : ?>
            <div class="col-sm-4 col-md-3">
                <?php if ($item['type'] == '[folder]') : ?>
                    <div class="card card-folder">
                        <div class="card-img-top ratio ratio-4x3">
                            <div class="d-flex justify-content-center align-items-center h-100">
                                <?= $this->Html->icon('folder', ['style' => 'font-size:4rem; color:#ccc']) ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?= $this->Html->link(
                                    $item['filename'],
                                    [
                                        'action' => 'images',
                                        '?' => [
                                            'folder' => $folder ? 
                                                $folder . DS . $item['filename'] : 
                                                $item['filename'],
                                            'target' => $target
                                        ]
                                    ],
                                    [
                                        'class' => 'folder stretched-link'
                                    ]
                                ) 
                            ?>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="card card-image">
                        <div class="card-img-top ratio ratio-4x3" style="background-image:url('<?= addslashes($path . $item['filename']) ?>')"></div>
                        <div class="card-body">
                            <?= $this->Html->link(
                                $item['filename'], 
                                $path . $item['filename'], 
                                [
                                    'class' => 'select-file stretched-link', 
                                    'data-target' => $target
                                ]
                            ) ?>
                        </div>
                    </div>
                <?php endif ?>
            </div>
        <?php endforeach ?>
    </div>
    <?= $this->element('FileManager/pagination') ?>
<?php $this->end(); ?>

<?php if ($showModal): ?>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
            <h5 class="modal-title"><?= __d('brammo/admin', 'Images') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= __d('brammo/admin', 'Close') ?>"></button>
            </div>
            <div class="modal-body" id="modal-filemanager-content">
                <?= $this->fetch('content') ?>
            </div>
        </div>
    </div>
<?php elseif ($this->request->is('ajax')): ?>
    <?= $this->fetch('content') ?>
<?php else: ?>
    <div class="container">
        <div class="card">
            <div class="card-body" id="modal-filemanager-content">
                <?= $this->fetch('content') ?>
            </div>
        </div>
    </div>
<?php endif ?>
