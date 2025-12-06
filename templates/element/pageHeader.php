<?php
/**
 * Page Header element
 * 
 * @var Brammo\Admin\View\AppView $this
 */

if ($this->get('hidePageHeader')) {
    return;
}
?>
<section class="page-header">
    <div>
        <?= $this->element('Brammo/Admin.breadcrumbs') ?>
        <h1><?= h($this->fetch('title')) ?></h1>
    </div>
    <div class="buttons">
        <?= $this->fetch('buttons') ?>
    </div>
</section>
