<?php
/**
 * FileManager index template
 * 
 * @var \Brammo\Admin\View\AppView $this
 * @var string $folder
 * @var string $filter
 */

use Cake\Core\Configure;

$fileIcons = Configure::read('Admin.FileManager.fileIcons') ?: [];
$imageTypes = Configure::read('Admin.FileManager.fileTypes.images') ?: [];
$imageMaxWidth = Configure::read('Admin.FileManager.imageMaxWidth');
$imageMaxHeight = Configure::read('Admin.FileManager.imageMaxHeight');

$this->assign('title', __d('brammo/admin', 'File Manager'));

// If in a folder, set up breadcrumbs and buttons
if ($folder) {

    $parts = explode('/', $folder);

    $upFolder = count($parts) > 1 ? implode('/', array_slice($parts, 0, -1)) : '';

    // Breadcrumbs
    $this->Breadcrumbs->add(__d('brammo/admin', 'File Manager'), [
        'action' => 'index'
    ]);
    $currentFolder = array_pop($parts);
    foreach ($parts as $i => $part) {
        $this->Breadcrumbs->add($part, [
            'action' => 'index', 
            '?' => [
                'folder' => implode('/', array_slice($parts, 0, $i + 1))
            ]
        ]);
    }
    $this->assign('title', $currentFolder);

    // Up one level button
    $this->append('buttons', 
        $this->Button->render(
            __d('brammo/admin', 'Up One Level'),
            [
                'action' => $this->request->getParam('action'), 
                '?' => ['folder' => $upFolder]
            ],
            [
                'variant' => 'secondary',
                'icon' => 'chevron-left',
                'style' => 'compact',
            ]
        )
    );
    
    // Create folder button
    $this->append('buttons', $this->Button->render(__d('brammo/admin', 'Create Folder'), 
        [
            'action' => 'createFolder', 
            '?' => compact('folder', 'filter')
        ],
        [
            'variant' => 'success',
            'icon' => 'folder-plus',
            'id' => 'btn-create-folder',
        ]
    ));
}

// Refresh button
$this->append('buttons',
    $this->Button->render(__d('brammo/admin', 'Refresh'), 
    [
        'action' => $this->request->getParam('action'), 
        '?' => compact('folder', 'filter')
    ],
    [
        'variant' => 'info',
        'icon' => 'arrow-repeat',
        'id' => 'btn-refresh',
    ]
));

if ($folder) {

    // Upload form
    $this->append('buttons');
        echo $this->Form->create(null, [
            'url' => ['action' => 'upload', '?' => compact('folder', 'filter')],
            'type' => 'file', 
            'id' => 'form-upload', 
            'class' => 'd-inline-block'
        ]);
        echo $this->Form->file('files.', ['style' => 'display:none', 'multiple']);
        echo $this->Form->button(
            $this->Html->icon('upload') . ' ' . __d('brammo/admin', 'Upload'), 
            [
                'type' => 'submit', 
                'class' => 'btn btn-primary', 
                'id' => 'btn-upload',
                'escapeTitle' => false
            ]
        );
        echo $this->Form->end();
    $this->end();
}

// Filter form
$this->append('buttons');
    echo $this->Form->create(null, [
        'url' => ['action' => 'index'], 
        'type' => 'get', 
        'id' => 'form-filter', 
        'class' => 'd-inline-block ms-2',
        'style' => 'max-width:10rem'
    ]);
    echo $this->Form->hidden('folder', ['value' => $folder]);
    echo $this->Form->text('filter', [
        'placeholder' => __d('brammo/admin', 'Filter by name'),
        'value' => $filter,
        'class' => 'form-control',
    ]);
    echo $this->Form->end();
$this->end();

$path = '/';
if ($folder) {
    $path .= $folder . '/';
}
?>
<div class="card">
    <div class="card-body gallery">
        <table class="table table-responsive">
            <thead>
                <tr>
                    <th class="w-1 text-end">#</th>
                    <th><?= __d('brammo/admin', 'Name') ?></th>
                    <th></th>
                    <th class="text-end"><?= __d('brammo/admin', 'Size') ?></th>
                    <th></th>
                    <th class="text-end"><?= __d('brammo/admin', 'Date') ?></th>
                    <th colspan="2" class="actions"><?= __d('brammo/admin', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $images = 0; foreach ($items as $i => $item) : ?>
                    <tr>
                        <td class="text-end"><?= $i + $first + 1 ?></td>
                        <?php if ($item['type'] == '[folder]') : ?>
                            <td>
                                <?= $this->Html->icon('folder') ?> 
                                <?php
                                $filename = $item['filename'];
                                if (!empty($folder)) {
                                    $filename = $folder . '/' . $item['filename'];
                                }
                                echo $this->Html->link(
                                    $item['filename'],
                                    ['action' => 'index', '?' => ['folder' => $filename]]
                                );
                                ?>
                            </td>
                            <td></td>
                            <td class="text-end"><?= $item['size'] ?> <?= __d('brammo/admin', 'file(s)') ?></td>
                            <td></td>
                            <td class="text-end"><?= $item['date'] ?></td>
                            <td></td>
                        <?php else: ?>
                            <td>
                                <?= $this->Html->icon($fileIcons[$item['type']] ?? $fileIcons['default'] ?? 'file-earmark') ?>
                                <?php
                                $options = [];
                                if (!empty($item['image'])) {
                                    $images++;
                                    $options['class'] = 'gallery-image';
                                }
                                $url = $path . $item['filename'];
                                echo $this->Html->link($item['filename'], $url, $options);
                                ?>
                            </td>
                            <td><?php
                                $isImage = in_array($item['type'], $imageTypes);
                                $hasSize = $isImage && !empty($item['width']) && !empty($item['height']);
                                $isOversized = $hasSize && $imageMaxWidth && $imageMaxHeight &&
                                    (($item['width'] > $imageMaxWidth) || 
                                    ($item['height'] > $imageMaxHeight));
                                if ($isImage) {
                                    echo $this->Html->link(
                                        $this->Html->image(
                                            $path . $item['filename'], 
                                            ['style' => 'max-width:50px']
                                        ),
                                        $url,
                                        ['escape' => false, 'class' => 'gallery-image']
                                    );
                                }
                            ?></td>
                            <td class="text-end"><?= $this->Number->toReadableSize($item['size']) ?></td>
                            <td class="text-center">
                                <?php if ($isImage && $hasSize) : ?>
                                    <span<?php if ($isOversized) { ?> class="text-danger"<?php } ?>>
                                        <?= $item['width'] ?> &times; <?= $item['height'] ?>
                                    </span>
                                <?php endif ?>
                            </td>
                            <td class="text-end"><?= $item['date'] ?></td>
                            <td class="action"><?php
                                if ($isOversized) {
                                    echo $this->Html->link(
                                        $this->Html->icon('wrench'),
                                        [
                                            'action' => 'fixImage', 
                                            '?' => [
                                                'folder' => $folder,
                                                'file' => $item['filename']
                                            ]
                                        ],
                                        [
                                            'escape' => false, 
                                            'class' => 'btn btn-warning btn-sm', 
                                            'title' => __d('brammo/admin', 'Fix Image Size')
                                        ]   
                                    );
                                }
                            ?></td>
                        <?php endif ?>
                        <td class="action"><?php
                            if ($item['type'] != '[folder]' || $item['size'] == 0) {
                                $url = [
                                    'action' => 'delete',
                                    '?' => compact('folder')
                                ];
                                if ($item['type'] == '[folder]') {
                                    $url['?']['deleteFolder'] = $item['filename'];
                                } else {
                                    $url['?']['deleteFile'] = $item['filename'];
                                }
                                echo $this->Form->postLink(
                                    $this->Html->icon('trash'),
                                    $url,
                                    [
                                        'escape' => false, 
                                        'class' => 'btn btn-danger btn-sm', 
                                        'title' => __d('brammo/admin', 'Delete'),
                                        'confirm' => __d('brammo/admin', 'Are you sure you want to delete?')
                                    ],
                                );
                            }
                        ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <?= $this->element('FileManager/pagination') ?>
    </div>
</div>
<?php $this->append('script') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const btnUpload = document.getElementById('btn-upload');
        const formUpload = document.getElementById('form-upload');

        if (btnUpload && formUpload) {

            const fileInput = formUpload.querySelector('input[type="file"]');

            btnUpload.addEventListener('click', function(e) {
                e.preventDefault();
                fileInput.click();
            });

            formUpload.addEventListener('change', function(e) {
                if (fileInput.files.length > 0) {
                    formUpload.submit();
                }
            });
        }
    });
</script>
<?php $this->end() ?>
