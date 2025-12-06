<?php
/**
 * Breadcrumbs element
 * 
 * @var Brammo\Admin\View\AppView $this
 */

use Cake\Core\Configure;

if (!$this->Breadcrumbs->getCrumbs()) {
    return;
}

$config = Configure::read('Admin.Home');

$homeUrl = $config['url'] ?? '/admin';
$homeTitle = $config['title'] ?? __d('brammo/admin', 'Home');

if (!empty($config['icon'])) {
    $this->Breadcrumbs->prepend($this->Html->icon($config['icon']), $homeUrl, ['title' => $homeTitle]);
} else {
    $this->Breadcrumbs->prepend($homeTitle, $homeUrl);
}

echo $this->Breadcrumbs->render();
