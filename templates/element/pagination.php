<?php
/**
 * Pagination element
 * 
 * Uses BootstrapUI pagination with icons.
 * 
 * @var Brammo\Admin\View\AppView $this
 */

// If total count of pages <= 1, don't show pagination
if ($this->Paginator->total() <= 1) {
    return;
}
?>
<div class="d-lg-flex justify-content-between">
    <?= $this->Paginator->links([
            'first' => $this->Html->icon('chevron-double-left'),
            'prev' => $this->Html->icon('chevron-left'),
            'next' => $this->Html->icon('chevron-right'),
            'last' => $this->Html->icon('chevron-double-right'),
            'escape' => false
        ]) 
    ?>
    <div class="mt-3 mt-lg-0">
        <?= $this->Paginator->counter(__d('brammo/admin', 'Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?>
    </div>
</div>
