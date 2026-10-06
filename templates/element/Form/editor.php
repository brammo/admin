<?php
/**
 * Admin HTML editor
 *
 * Loads the content editor and enables File Manager browsing for images and files.
 * Admin.Editor settings are passed through and override Content.Editor.
 *
 * @var \Brammo\Admin\View\AppView $this
 */

use Cake\Core\Configure;
use Cake\Routing\Router;

$settings = Configure::read('Admin.Editor');
if (!is_array($settings)) {
    $settings = [];
}

$imagesUrl = Router::url([
    'plugin' => 'Brammo/Admin',
    'controller' => 'FileManager',
    'action' => 'browseImages',
]);

$filesUrl = Router::url([
    'plugin' => 'Brammo/Admin',
    'controller' => 'FileManager',
    'action' => 'browseFiles',
]);

$this->Html->script('Brammo/Admin.file-browser', ['block' => true]);

$editorOptions = [
    'imagesUrl' => $imagesUrl,
    'filesUrl' => $filesUrl,
];

foreach (['height', 'cleanOnPaste', 'statusBar', 'tableClass'] as $key) {
    if (array_key_exists($key, $settings)) {
        $editorOptions[$key] = $settings[$key];
    }
}

echo $this->element('Brammo/Content.editor', $editorOptions);
