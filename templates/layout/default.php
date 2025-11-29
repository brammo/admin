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
    <?= $this->fetch('meta') ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@7.1.0/css/fontawesome.min.css" integrity="sha256-G5bovI+uHM2gCO6EwO9PFQcclSBYzTaxY6DXks3YxBU=" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap">
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
            <?= $this->fetch('content') ?>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@7.1.0/js/all.min.js" integrity="sha256-Z6/mj/QZDUfTjL6nkq7pwkHNZpkVY8Xk8Appn4bTLXs=" crossorigin="anonymous"></script>    <?= $this->Html->script('Brammo/Admin.main') ?>
    <?= $this->fetch('script') ?>
</body>
</html>
