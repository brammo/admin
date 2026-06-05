<?php
/**
 * Minimal layout for embedded file browser (e.g. TinyMCE image picker iframe)
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
<body>
    <div class="p-3">
        <?= $this->fetch('content') ?>
    </div>
    <?= $this->element('Brammo/Admin.Layout/script') ?>
    <?= $this->fetch('script') ?>
</body>
</html>
