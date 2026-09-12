<?php
/**
 * Grip handle for drag-and-drop reorder
 *
 * @var \Brammo\Admin\View\AppView $this
 * @var string|null $title
 */

use function Cake\Core\h;
use function Cake\I18n\__d;

$title = $title ?? __d('brammo/admin', 'Drag to reorder');
?>
<span
    class="text-secondary"
    data-sortable-handle
    style="cursor: grab"
    title="<?= h($title) ?>"
>
    <?= $this->Html->icon('grip-vertical') ?>
</span>
