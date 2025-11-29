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
<section class="page-header">
    <div>
        <?= $this->Breadcrumbs->render() ?>
        <h1><?= h($this->fetch('title')) ?></h1>
    </div>
    <div class="buttons">
        <?= $this->fetch('buttons') ?>
    </div>
</section>
