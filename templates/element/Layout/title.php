<?php
/**
 * Title element for HTML head
 * 
 * @var \Brammo\Admin\View\AppView $this
 */

use Cake\Core\Configure;

$title = $this->fetch('title');
$adminTitle = (string)Configure::read('Admin.Layout.title');
if ($adminTitle) {
    if ($title) {
        $title .= ' | ';
    }
    $title .= $adminTitle;
}
?>
<title><?= $title ?></title>
