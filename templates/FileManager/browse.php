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

$action = $this->request->getParam('action');
?>
<div class="file-browser-header">
    <div class="d-lg-flex">
        <div class="d-flex mb-3 mb-lg-0">
            <?php if ($folder): ?>
                <div class="me-2">
                    <?php 
                        $upFolder = dirname($folder);
                        if ($upFolder === '.') {
                            $upFolder = '';
                        }
                    ?>
                    <?= $this->Html->link(
                            $this->Html->icon('chevron-left'),
                            [
                                'action' => $action, 
                                '?' => [
                                    'folder' => $upFolder,
                                    'target' => $target
                                ],
                            ],
                            [
                                'escape' => false, 
                                'class' => 'folder btn btn-light bg-white btn-sm', 
                                'title' => __d('brammo/admin', 'Back')
                            ]
                        );
                    ?>
                </div>
            <?php endif ?>
            <div class="me-2 py-1 px-3 bg-white rounded fw-bolder">
                <?= $this->Html->icon('folder') ?> /<?= $folder ?>
            </div>
            <?php if ($folder): ?>
                <div>
                    <?= $this->Form->create(null, [
                            'url' => ['action' => 'createFolder', '?' => compact('folder')],
                            'class' => 'create-folder-form d-inline-block', 
                            'style' => 'max-width:10rem;'
                        ]) 
                    ?>
                        <?= $this->Form->button($this->Html->icon('folder-plus'),
                                [
                                    'class' => 'create-folder btn btn-success btn-sm', 
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
                            <?= $this->Form->button($this->Html->icon('folder-plus'), [
                                    'type' => 'submit', 
                                    'class' => 'btn btn-success btn-sm', 
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
                        'class' => 'refresh btn btn-info btn-sm', 
                        'title' => __d('brammo/admin', 'Refresh')
                    ]
                );
            ?> 
        </div>
        <?php if ($folder): ?>
            <div class="ms-lg-2">
                <?= $this->Form->create(null, [
                        'url' => ['action' => 'upload', '?' => compact('folder', 'target')],
                        'type' => 'file', 
                        'class' => 'upload-form d-inline-block'
                    ]) 
                ?>
                    <?= $this->Form->file('files.', ['style' => 'display:none', 'multiple']) ?>
                    <?= $this->Form->button(
                        $this->Html->icon('upload'), 
                        [
                            'type' => 'submit', 
                            'class' => 'upload btn btn-primary btn-sm', 
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
                        'action' => $action
                    ], 
                    'type' => 'get', 
                    'class' => 'filter-form', 
                    'style' => 'max-width:10rem;'
                ]) 
            ?>
                <?= $this->Form->hidden('folder', ['value' => $folder]) ?>
                <?= $this->Form->hidden('target', ['value' => $target]) ?>
                <div class="input-group">
                    <?= $this->Form->text('filter', [
                            'value' => $filter, 
                            'class' => 'form-control form-control-sm', 
                            'placeholder' => __d('brammo/admin', 'Filter by name')
                        ]) 
                    ?>
                    <?= $this->Form->button($this->Html->icon('filter'), [
                            'type' => 'submit', 
                            'class' => 'filterbtn btn-secondary btn-sm', 
                            'title' => __d('brammo/admin', 'Filter'),
                            'escapeTitle' => false
                        ]) 
                    ?>
                </div>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
<div class="file-browser-images row mt-2 g-2">
    <?php foreach ($items as $item) : ?>
        <div class="col-sm-4 col-md-3">
            <?php if ($item['type'] == '[folder]') : ?>
                <div class="card card-folder">
                    <div class="card-img-top ratio ratio-4x3">
                        <div class="d-flex justify-content-center align-items-center h-100">
                            <?= $this->Html->icon('folder', ['style' => 'font-size:4rem']) ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?= $this->Html->link(
                                $item['filename'],
                                [
                                    'action' => $action,
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
                                'class' => 'select stretched-link',
                                'data-type' => 'image', 
                                'data-target' => $target
                            ]
                        ) ?>
                    </div>
                </div>
            <?php endif ?>
        </div>
    <?php endforeach ?>
</div>
<?= $this->element('FileManager/pagination', [
        'folder' => $folder,
        'target' => $target,
        'filter' => $filter,
        'action' => $action
    ]) 
?>
