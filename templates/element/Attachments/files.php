<?php
/**
 * Attachment files section (list + multi-upload + sort)
 *
 * @var \Brammo\Admin\View\AppView $this
 * @var iterable<\Cake\ORM\Entity>|null $items
 * @var string|array|null $uploadUrl
 * @var string|array|null $sortUrl
 * @var string|array|null $addUrl
 * @var string|null $editAction
 * @var string|null $deleteAction
 * @var string|null $title
 * @var string|null $addTitle
 * @var string|null $uploadLabel
 * @var string|null $accept
 * @var string|null $id
 */

use Cake\Routing\Router;
use function Cake\Core\h;
use function Cake\I18n\__d;

$items = $items ?? [];
$filesList = is_array($items) ? $items : iterator_to_array($items);
$uploadUrl = $uploadUrl ?? null;
$sortUrl = $sortUrl ?? null;
$addUrl = $addUrl ?? null;
$editAction = $editAction ?? null;
$deleteAction = $deleteAction ?? null;
$title = $title ?? __d('brammo/admin', 'Files');
$addTitle = $addTitle ?? __d('brammo/admin', 'Add file');
$uploadLabel = $uploadLabel ?? __d('brammo/admin', 'Upload files');
$accept = $accept ?? '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx';
$id = $id ?? 'files';
$csrfToken = $this->getRequest()->getAttribute('csrfToken');
?>
<a name="files"></a>
<div class="d-md-flex mb-3">
    <h3 class="me-3"><?= h($title) ?></h3>
    <?php if ($addUrl): ?>
        <div class="me-2">
            <?= $this->Button->create($addUrl, ['title' => $addTitle]) ?>
        </div>
    <?php endif ?>
    <?php if ($uploadUrl): ?>
        <div class="me-2">
            <?= $this->element('Brammo/Admin.multiupload', [
                'label' => $uploadLabel,
                'id' => $id,
                'url' => $uploadUrl,
                'accept' => $accept,
            ]) ?>
        </div>
    <?php endif ?>
</div>
<?php if ($filesList): ?>
    <div class="card mb-3">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <?php if ($sortUrl): ?>
                            <th style="width:2rem"></th>
                        <?php endif ?>
                        <th><?= h(__d('brammo/admin', 'Title')) ?></th>
                        <th><?= h(__d('brammo/admin', 'File')) ?></th>
                        <?php if ($editAction || $deleteAction): ?>
                            <th colspan="2" class="actions"><?= h(__d('brammo/admin', 'Actions')) ?></th>
                        <?php endif ?>
                    </tr>
                </thead>
                <tbody
                    <?php if ($sortUrl): ?>
                        data-sortable
                        data-url="<?= h(is_array($sortUrl) ? Router::url($sortUrl) : (string)$sortUrl) ?>"
                        data-csrf-token="<?= h((string)$csrfToken) ?>"
                    <?php endif ?>
                >
                    <?php foreach ($filesList as $file): ?>
                        <tr data-id="<?= (int)$file->id ?>">
                            <?php if ($sortUrl): ?>
                                <td class="text-secondary align-middle">
                                    <?= $this->element('Brammo/Admin.Sortable/handle') ?>
                                </td>
                            <?php endif ?>
                            <td><?= h((string)$file->get('title')) ?></td>
                            <td>
                                <?php
                                $filename = (string)$file->get('filename');
                                if ($filename !== '') {
                                    echo $this->Html->link($filename, $filename, ['target' => '_blank']);
                                }
                                ?>
                            </td>
                            <?php if ($editAction): ?>
                                <td class="action">
                                    <?= $this->Button->editCompact(['action' => $editAction, $file->id]) ?>
                                </td>
                            <?php endif ?>
                            <?php if ($deleteAction): ?>
                                <td class="action">
                                    <?= $this->Button->deleteCompact(['action' => $deleteAction, $file->id]) ?>
                                </td>
                            <?php endif ?>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($sortUrl): ?>
        <?= $this->element('Brammo/Admin.sortable') ?>
    <?php endif ?>
<?php endif ?>
