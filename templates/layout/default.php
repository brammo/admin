<?php
/**
 * Default HTML layout
 * 
 * @var \Brammo\Admin\View\AppView $this
 */

use Cake\I18n\I18n;

$lang = I18n::getLocale();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
    <title><?= $this->fetch('title') ?></title>
    <?= $this->fetch('meta') ?>
    <?= $this->element('Brammo/Admin.Layout/css') ?>
    <?= $this->Html->css('Brammo/Admin.styles') ?>
    <?= $this->fetch('css') ?>
</head>
<body>
    <header class="main-header navbar navbar-expand">
        <?= $this->element('Brammo/Admin.Header/toggler') ?>
        <?= $this->element('Brammo/Admin.Header/menu') ?>
    </header>
    <aside class="main-sidebar">
        <?= $this->element('Brammo/Admin.Sidebar/brand') ?>
        <?= $this->element('Brammo/Admin.Sidebar/menu') ?>
    </aside>
    <main class="main-content">
        <?= $this->element('Brammo/Admin.pageHeader') ?>
        <div class="page-content">
            <?= $this->Flash->render() ?>
            <?= $this->fetch('content') ?>
        </div>
    </main>
    <?= $this->element('Brammo/Admin.Layout/script') ?>
    <?= $this->Html->script('Brammo/Admin.main') ?>
    <?= $this->fetch('script') ?>
</body>
</html>
