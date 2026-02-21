<?php
/**
 * User login layout
 * 
 * @var \Brammo\Admin\View\AppView $this
 */

use Cake\I18n\I18n;
?>
<!DOCTYPE html>
<html lang="<?= I18n::getLocale() ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
    <?= $this->element('Brammo/Admin.Layout/title') ?>
    <?= $this->fetch('meta') ?>
    <?= $this->element('Brammo/Admin.Layout/css') ?>
    <?= $this->Html->css('Brammo/Admin.styles') ?>
    <?= $this->fetch('css') ?>
</head>
<body class="sidebar-mini">
    <?= $this->fetch('content') ?>
    <?= $this->element('Brammo/Admin.Layout/script') ?>
    <?= $this->Html->script('Brammo/Admin.main') ?>
    <?= $this->fetch('script') ?>
</body>
</html>
