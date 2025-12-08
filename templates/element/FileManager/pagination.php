<?php 
/**
 * FileManager pagination element
 * 
 * @var \App\View\AppView $this
 * @var string $folder
 * @var string $target
 * @var string $filter
 * @var int $page
 * @var int $pages
 */

use Cake\Routing\Router;

if ($pages < 2) {
    return;
}

$url = [
    'action' => $this->request->getParam('action'), 
    '?' => []
];

if (!empty($folder)) {
    $url['?']['folder'] = $folder;
}

if (!empty($target)) {
    $url['?']['target'] = $target;
}

if (!empty($filter)) {
    $url['?']['filter'] = $filter;
}

$url['?']['page'] = 1;
?>
<div class="mt-3">
    <ul class="pagination">
        <li class="page-item<?php if ($page == 1) { ?> disabled<?php } ?>">
            <a href="<?= Router::url($url) ?>" class="page-link">
                <?= $this->Html->icon('chevron-left') ?>
            </a>
        </li>
        <li class="page-item<?php if ($page == 1) { ?> active<?php } ?>">
            <a href="<?= Router::url($url) ?>" class="page-link">1</a>
        </li>
        <?php if ($page > 4): ?>
            <li>
                <span class="page-link">...</span>
            </li>
        <?php endif ?>
        <?php for ($i = $page - 2; $i < $page + 3; $i++): ?>
            <?php if ($i > 1 && $i < $pages): ?>
                <li class="page-item<?php if ($page == $i) { ?> active<?php } ?>">
                    <?php 
                    $url['?']['page'] = $i; 
                    ?>
                    <a href="<?= Router::url($url) ?>" class="page-link"><?= $i ?></a>
                </li>
            <?php endif ?>
        <?php endfor?>
        <?php if ($page < $pages - 3): ?>
            <li class="page-item">
                <span class="page-link">...</span>
            </li>
        <?php endif ?>
        <li class="page-item<?php if ($page == $pages) { ?> active<?php } ?>">
            <?php 
            $url['?']['page'] = $pages; 
            ?>
            <a href="<?= Router::url($url) ?>" class="page-link"><?= $pages ?></a>
        </li>
        <li class="page-item<?php if ($page == $pages) { ?> disabled<?php } ?>">
            <?php 
            $url['?']['page'] = $page + 1; 
            ?>
            <a href="<?= Router::url($url) ?>" class="page-link">
                <?= $this->Html->icon('chevron-right') ?>
            </a>
        </li>
    </ul>
</div>
