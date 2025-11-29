<?php
/**
 * Layout CSS element
 * 
 * Renders CSS assets from Admin.Layout configuration
 * 
 * @var \Brammo\Admin\View\AppView $this
 */

use Cake\Core\Configure;

$layoutConfig = Configure::read('Admin.Layout', []);
$cssDefaults = $layoutConfig['cssDefaults'] ?? [];
$cssAppend = $layoutConfig['css'] ?? [];
$fontsConfig = $layoutConfig['fonts'] ?? ['enabled' => true, 'preconnect' => [], 'files' => []];

// Merge defaults with appended assets
$cssFiles = array_merge($cssDefaults, $cssAppend);

// Render CSS files from config
foreach ($cssFiles as $css) {
    if (is_string($css)) {
        if ($css[0] === '<') {
            echo $css;
        } else {    
            echo $this->Html->css($css);
        }
    } elseif (is_array($css)) {
        $options = [];
        if (!empty($css['integrity'])) {
            $options['integrity'] = $css['integrity'];
        }
        if (!empty($css['crossorigin'])) {
            $options['crossorigin'] = $css['crossorigin'];
        }
        echo $this->Html->css($css['url'], $options);
    }
    echo "\n";
}

// Render fonts if enabled
if (!empty($fontsConfig['enabled'])) {
    foreach ($fontsConfig['preconnect'] ?? [] as $preconnect) {
        $options = ['rel' => 'preconnect', 'href' => $preconnect];
        if ($preconnect === 'https://fonts.gstatic.com') {
            $options['crossorigin'] = true;
        }
        echo $this->Html->tag('link', null, $options);
        echo "\n";
    }
    foreach ($fontsConfig['files'] ?? [] as $font) {
        echo $this->Html->css($font);
        echo "\n";
    }
}
