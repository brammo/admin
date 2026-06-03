<?php
/**
 * Page Header element
 * 
 * @var \Brammo\Admin\View\AppView $this
 */

if ($this->get('hidePageHeader')) {
    return;
}

$pageHeading = $this->get('pageHeading', $this->fetch('title'));
?>
<section class="page-header">
    <?= $this->element('Brammo/Admin.breadcrumbs') ?>
    <div class="d-lg-flex align-items-start">
        <h1><?= h($pageHeading) ?></h1>
        <div class="buttons">
            <?= $this->fetch('buttons') ?>
        </div>
    </div>
</section>
