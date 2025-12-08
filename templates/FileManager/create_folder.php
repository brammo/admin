<?php
/**
 * File Manager - Create Folder Template
 * 
 * @var \Brammo\View\AppView $this
 * @var string $folder
 */

$this->assign('title', __d('brammo/admin', 'Create Folder'));

$parts = explode('/', $folder);

// Breadcrumbs
$this->Breadcrumbs->add(__d('brammo/admin', 'File Manager'), [
    'action' => 'index'
]);

foreach ($parts as $i => $part) {
    $this->Breadcrumbs->add($part, [
        'action' => 'index', 
        '?' => [
            'folder' => implode('/', array_slice($parts, 0, $i + 1))
        ]
    ]);
}

?>
<div class="container">
    <div class="card">
        <div class="card-body">
            <?= $this->Form->create(null) ?>
            <?= $this->Form->control('folder', [
                    'label' => __d('brammo/admin', 'Folder Name'),
                    'required' => true,
                ]) 
            ?>
            <?= $this->Form->button(__d('brammo/admin', 'Create'), ['class' => 'btn btn-primary']) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>