<?php
/**
 * Layout Script element
 * 
 * Renders JavaScript assets from Admin.Layout configuration
 * 
 * @var \Brammo\Admin\View\AppView $this
 */

use Cake\Core\Configure;

$layoutConfig = Configure::read('Admin.Layout', []);
$scriptDefaults = $layoutConfig['scriptDefaults'] ?? [];
$scriptAppend = $layoutConfig['script'] ?? [];

// Merge defaults with appended assets
$scriptFiles = array_merge($scriptDefaults, $scriptAppend);

// Render JS files from config
foreach ($scriptFiles as $script) {
    if (is_string($script)) {
        if ($script[0] === '<') {
            echo $script;
        } else {
            echo $this->Html->script($script);
        }
    } else {
        $options = [];
        if (!empty($script['integrity'])) {
            $options['integrity'] = $script['integrity'];
        }
        if (!empty($script['crossorigin'])) {
            $options['crossorigin'] = $script['crossorigin'];
        }
        echo $this->Html->script($script['url'], $options);
    }
    echo "\n";
}
