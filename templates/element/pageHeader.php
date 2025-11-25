<?php
/**
 * Page Header element
 * 
 * @var Brammo\Admin\View\AppView $this
 */

use Cake\Core\Configure;

if ($this->get('hidePageHeader')) {
    return;
}

$config = Configure::read('Admin.Home');

$this->Breadcrumbs->prepend(
    $this->Html->icon($config['icon']), $config['url'], ['title' => $config['title']]);
?>
<section class="page-header p-3">
    <div class="container">
        <div class="d-md-flex align-items-center">
            <div>
                <?= $this->Breadcrumbs->render() ?>
                <h1 class="mt-0 mb-2 mb-md-0 fs-3 fw-bold">
                    <?= h($this->fetch('title')) ?>
                </h1>
            </div>
            <div class="buttons ms-md-auto">
                <?= $this->fetch('buttons') ?>
            </div>
        </div>
    </div>
</section>
