<?php
/**
 * Attachment images gallery section (grid + multi-upload + sort)
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
 * @var bool|null $lightbox
 * @var int|null $thumbSize
 */

use Cake\Routing\Router;
use function Cake\Core\h;
use function Cake\I18n\__d;

$items = $items ?? [];
$imagesList = is_array($items) ? $items : iterator_to_array($items);
$uploadUrl = $uploadUrl ?? null;
$sortUrl = $sortUrl ?? null;
$addUrl = $addUrl ?? null;
$editAction = $editAction ?? null;
$deleteAction = $deleteAction ?? null;
$title = $title ?? __d('brammo/admin', 'Images');
$addTitle = $addTitle ?? __d('brammo/admin', 'Add image');
$uploadLabel = $uploadLabel ?? __d('brammo/admin', 'Upload images');
$accept = $accept ?? 'image/*';
$id = $id ?? 'images';
$lightbox = $lightbox ?? true;
$thumbSize = $thumbSize ?? 300;
$csrfToken = $this->getRequest()->getAttribute('csrfToken');
$galleryClass = 'gallery-' . $id;
?>
<a name="images"></a>
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
<?php if ($imagesList): ?>
    <div
        class="row gallery <?= h($galleryClass) ?>"
        <?php if ($sortUrl): ?>
            data-sortable
            data-url="<?= h(is_array($sortUrl) ? Router::url($sortUrl) : (string)$sortUrl) ?>"
            data-csrf-token="<?= h((string)$csrfToken) ?>"
        <?php endif ?>
    >
        <?php foreach ($imagesList as $image): ?>
            <div class="col-sm-6 col-md-4 col-lg-3" data-id="<?= (int)$image->id ?>">
                <div class="card mb-3">
                    <div class="card-body text-center p-2">
                        <?php
                        $filename = (string)$image->get('filename');
                        if ($filename !== '') {
                            echo $this->Html->link(
                                $this->Html->image($this->Image->fit($filename, $thumbSize), [
                                    'class' => 'img-fluid w-100',
                                ]),
                                $filename,
                                ['escape' => false, 'class' => 'gallery-image d-block'],
                            );
                        }
                        $imageTitle = (string)$image->get('title');
                        if ($imageTitle !== '') {
                            echo '<div>' . h($imageTitle) . '</div>';
                        }
                        ?>
                    </div>
                    <div class="card-footer p-2 d-flex align-items-center justify-content-between">
                        <?php if ($sortUrl): ?>
                            <?= $this->element('Brammo/Admin.Sortable/handle') ?>
                        <?php else: ?>
                            <span></span>
                        <?php endif ?>
                        <span>
                            <?php if ($editAction): ?>
                                <?= $this->Button->editCompact(['action' => $editAction, $image->id]) ?>
                            <?php endif ?>
                            <?php if ($deleteAction): ?>
                                <?= $this->Button->deleteCompact(['action' => $deleteAction, $image->id]) ?>
                            <?php endif ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>
    <?php if ($lightbox): ?>
        <?= $this->element('Brammo/Content.lightgallery', [
            'selector' => '.' . $galleryClass,
            'itemSelector' => 'a.gallery-image',
        ]) ?>
    <?php endif ?>
    <?php if ($sortUrl): ?>
        <?= $this->element('Brammo/Admin.sortable') ?>
    <?php endif ?>
<?php endif ?>
