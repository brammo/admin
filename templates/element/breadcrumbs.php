<?php
/**
 * Breadcrumbs element
 * 
 * @var \Brammo\Admin\View\AppView $this
 */

use Cake\Core\Configure;

$config = Configure::read('Admin.Home');

$homeUrl = $config['url'] ?? '/admin';
$homeTitle = $config['title'] ?? __d('brammo/admin', 'Home');
$homeOptions = [];

if (!empty($config['icon'])) {
    $homeOptions['icon'] = $config['icon'];
    $homeOptions['title'] = $homeTitle;
    $homeTitle = $this->Html->icon($config['icon']);
}

$this->Breadcrumbs->prepend($homeTitle, $homeUrl, $homeOptions);

echo $this->Breadcrumbs->render();
