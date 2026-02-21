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
    <div>
        <?= $this->element('Brammo/Admin.breadcrumbs') ?>
        <h1><?= h($pageHeading) ?></h1>
    </div>
    <div class="buttons">
        <?= $this->fetch('buttons') ?>
    </div>
</section>
