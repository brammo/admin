<?php
/**
 * FileManager header element
 * 
 * @var \App\View\AppView $this
 * @var string $folder
 * @var string $target
 * @var string $filter
 */

?>
<div class="d-lg-flex">
    <div class="d-flex mb-3 mb-lg-0">
        <?php if ($folder): ?>
            <div class="me-2">
                <?= $this->Html->link(
                        $this->Html->icon('chevron-left'),
                        [
                            'action' => $this->request->getParam('action'), 
                            '?' => [
                                'folder' => substr($folder, 0, strrpos($folder, '/')),
                                'target' => $target
                            ],
                        ],
                        [
                            'escape' => false, 
                            'class' => 'folder btn btn-light btn-sm', 
                            'title' => __d('brammo/admin', 'Back')
                        ]
                    );
                ?>
            </div>
        <?php endif ?>
        <div class="me-2 py-1 px-3 bg-light rounded fw-bolder"><?= $this->Html->icon('folder') ?> /<?= $folder ?></div>
        <?php if ($folder): ?>
            <div>
                <?= $this->Form->create(null, [
                        'url' => ['action' => 'createFolder', '?' => ['folder' => $folder]],
                        'id' => 'form-create-folder', 
                        'class' => 'd-inline-block ms-2', 
                        'style' => 'max-width:10rem;'
                    ]) 
                ?>
                    <?= $this->Form->button($this->Html->icon('folder-plus'),
                            [
                                'class' => 'btn btn-success btn-sm', 
                                'id' => 'btn-create-folder', 
                                'title' => __d('brammo/admin', 'Create Folder'),
                                'escapeTitle' => false, 
                            ]
                        );
                    ?>
                    <div class="input-group" style="display:none;">
                        <?= $this->Form->text('folder', [
                                'class' => 'form-control form-control-sm', 
                                'placeholder' => __d('brammo/admin', 'Folder Name')
                            ]) 
                        ?>
                        <?= $this->Form->button($this->Html->icon('check'), [
                                'type' => 'submit', 
                                'class' => 'btn btn-primary btn-sm', 
                                'title' => __d('brammo/admin', 'Create'),
                                'escapeTitle' => false
                            ]) 
                        ?>
                    </div>
                <?= $this->Form->end() ?>
            </div>
        <?php endif ?>
    </div>
    <div class="ms-lg-auto">
        <?= $this->Html->link(
                $this->Html->icon('arrow-repeat'),
                [
                    'action' => $this->request->getParam('action'), 
                    '?' => compact('folder', 'target', 'filter')
                ],
                [
                    'escape' => false, 
                    'class' => 'btn btn-info btn-sm', 
                    'id' => 'button-refresh',
                    'title' => __d('brammo/admin', 'Refresh')
                ]
            );
        ?> 
    </div>
    <?php if ($folder && !empty($showUpload)): ?>
        <div class="ms-lg-2">
            <?= $this->Form->create(null, [
                    'url' => ['action' => 'upload', '?' => compact('folder', 'target')],
                    'type' => 'file', 
                    'id' => 'form-upload', 
                    'class' => 'd-inline-block'
                ]) 
            ?>
                <?= $this->Form->file('files.', ['style' => 'display:none', 'multiple']) ?>
                <?= $this->Form->button(
                    $this->Html->icon('upload'), 
                    [
                        'type' => 'submit', 
                        'class' => 'btn btn-primary btn-sm', 
                        'id' => 'button-upload',
                        'title' => __d('brammo/admin', 'Upload'),
                        'escapeTitle' => false
                    ]
                ); 
            ?>
            <?= $this->Form->end() ?>
        </div>
    <?php endif ?>
    <div class="ms-lg-2">
        <?= $this->Form->create(null, [
                'url' => [
                    'action' => $this->request->getParam('action'), 
                    '?' => compact('folder', 'target')
                ], 
                'type' => 'get', 
                'id' => 'form-filter', 
                'style' => 'max-width:10rem;'
            ]) 
        ?>
            <div class="input-group">
                <?= $this->Form->text('filter', [
                        'value' => $filter, 
                        'class' => 'form-control form-control-sm', 
                        'placeholder' => __d('brammo/admin', 'Filter by name')
                    ]) 
                ?>
                <?= $this->Form->button($this->Html->icon('filter'), [
                        'type' => 'submit', 
                        'class' => 'btn btn-secondary btn-sm', 
                        'id' => 'button-search', 
                        'title' => __d('brammo/admin', 'Filter'),
                        'escapeTitle' => false
                    ]) 
                ?>
            </div>
        <?= $this->Form->end() ?>
    </div>
</div>
